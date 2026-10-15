<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_wallets_categories_and_transactions(): void
    {
        $user = User::create([
            'name' => 'Andi Pratama',
            'email' => 'andi@example.com',
            'password' => bcrypt('password123'),
            'telegram_chat_id' => 987654321,
            'telegram_username' => 'andipratama',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'andi@example.com',
            'telegram_chat_id' => 987654321,
        ]);

        // 1. Create Wallet
        $wallet = $user->wallets()->create([
            'name' => 'BCA Utama',
            'type' => 'bank',
            'balance' => 1500000,
            'is_default' => true,
        ]);

        $this->assertIsInt($wallet->balance);
        $this->assertEquals(1500000, $wallet->balance);
        $this->assertTrue($wallet->is_default);

        // 2. Create Category
        $category = $user->categories()->create([
            'name' => 'Makanan & Minuman',
            'type' => 'expense',
            'icon' => '🍔',
            'color' => '#f59e0b',
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Makanan & Minuman',
            'type' => 'expense',
        ]);

        // 3. Create Transaction without transacted_at to test auto-timestamping
        $transaction = $user->transactions()->create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'notes' => 'Makan Siang Nasi Padang',
            'source' => 'telegram_bot',
        ]);

        $this->assertNotNull($transaction->transacted_at);
        $this->assertIsInt($transaction->amount);
        $this->assertEquals(50000, $transaction->amount);
        $this->assertEquals('telegram_bot', $transaction->source);
        $this->assertEquals('BCA Utama', $transaction->wallet->name);
        $this->assertEquals('Makanan & Minuman', $transaction->category->name);
    }
}
