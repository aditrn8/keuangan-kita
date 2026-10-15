<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Services\Telegram\TelegramClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
    }

    public function test_webhook_rejects_unauthorized_secret_when_configured(): void
    {
        config(['services.telegram.webhook_secret' => 'super-secret-token-123']);

        // Request tanpa header
        $response = $this->postJson('/api/telegram/webhook', [
            'message' => ['text' => '/saldo'],
        ]);
        $response->assertStatus(403);

        // Request dengan secret salah
        $response = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
        ])->postJson('/api/telegram/webhook', [
            'message' => ['text' => '/saldo'],
        ]);
        $response->assertStatus(403);

        // Request dengan secret benar
        $response = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'super-secret-token-123',
        ])->postJson('/api/telegram/webhook', [
            'message' => ['text' => '/help'],
        ]);
        $response->assertStatus(200);
    }

    public function test_catat_expense_records_transaction_and_deducts_balance(): void
    {
        config(['services.telegram.webhook_secret' => null]);

        $user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => bcrypt('password'),
            'telegram_chat_id' => 998877,
        ]);

        $wallet = $user->wallets()->create([
            'name' => 'BCA',
            'type' => 'bank',
            'balance' => 500000,
            'is_default' => true,
        ]);

        $payload = [
            'message' => [
                'message_id' => 202,
                'chat' => ['id' => 998877],
                'from' => ['id' => 998877, 'first_name' => 'Budi'],
                'text' => '/catat 50000 Nasi Padang + Es Teh',
            ],
        ];

        $response = $this->postJson('/api/telegram/webhook', $payload);
        $response->assertStatus(200);

        // Pastikan transaksi berhasil tercatat
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'amount' => 50000,
            'notes' => 'Nasi Padang + Es Teh',
            'source' => 'telegram_bot',
        ]);

        // Pastikan saldo dompet berkurang secara tepat (500.000 - 50.000 = 450.000)
        $this->assertEquals(450000, $wallet->fresh()->balance);
    }

    public function test_masuk_income_records_transaction_and_increments_balance(): void
    {
        config(['services.telegram.webhook_secret' => null]);

        $user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => bcrypt('password'),
            'telegram_chat_id' => 998877,
        ]);

        $wallet = $user->wallets()->create([
            'name' => 'Dompet Utama',
            'type' => 'cash',
            'balance' => 100000,
            'is_default' => true,
        ]);

        $payload = [
            'message' => [
                'message_id' => 203,
                'chat' => ['id' => 998877],
                'from' => ['id' => 998877, 'first_name' => 'Budi'],
                'text' => '/masuk 1.5jt bonus freelance',
            ],
        ];

        $response = $this->postJson('/api/telegram/webhook', $payload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 1500000,
            'notes' => 'bonus freelance',
        ]);

        // Saldo bertambah (100.000 + 1.500.000 = 1.600.000)
        $this->assertEquals(1600000, $wallet->fresh()->balance);
    }

    public function test_saldo_command_returns_success(): void
    {
        config(['services.telegram.webhook_secret' => null]);

        $user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => bcrypt('password'),
            'telegram_chat_id' => 998877,
        ]);

        $user->wallets()->create([
            'name' => 'BCA',
            'type' => 'bank',
            'balance' => 750000,
            'is_default' => true,
        ]);

        $payload = [
            'message' => [
                'message_id' => 204,
                'chat' => ['id' => 998877],
                'from' => ['id' => 998877, 'first_name' => 'Budi'],
                'text' => '/saldo',
            ],
        ];

        $response = $this->postJson('/api/telegram/webhook', $payload);
        $response->assertStatus(200);
    }

    public function test_undo_callback_button_reverses_balance_and_deletes_transaction(): void
    {
        config(['services.telegram.webhook_secret' => null]);

        $user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => bcrypt('password'),
            'telegram_chat_id' => 998877,
        ]);

        $wallet = $user->wallets()->create([
            'name' => 'BCA',
            'type' => 'bank',
            'balance' => 450000, // Saldo setelah pengeluaran 50k
            'is_default' => true,
        ]);

        $transaction = $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'amount' => 50000,
            'notes' => 'Kopi Starbucks salah catat',
            'source' => 'telegram_bot',
            'transacted_at' => now(),
        ]);

        $callbackPayload = [
            'callback_query' => [
                'id' => 'cb_query_999',
                'data' => 'undo_trx_' . $transaction->id,
                'message' => [
                    'message_id' => 555,
                    'chat' => ['id' => 998877],
                ],
            ],
        ];

        $response = $this->postJson('/api/telegram/webhook', $callbackPayload);
        $response->assertStatus(200);

        // Saldo dikembalikan (450.000 + 50.000 = 500.000)
        $this->assertEquals(500000, $wallet->fresh()->balance);

        // Transaksi terhapus
        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }
}
