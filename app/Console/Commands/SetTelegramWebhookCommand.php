<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook {url? : Domain publik lengkap (contoh: https://keuangan-kita.vercel.app)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mendaftarkan webhook Telegram Bot API secara otomatis';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $token = config('services.telegram.bot_token');
        $secret = config('services.telegram.webhook_secret');
        $url = $this->argument('url') ?? config('app.url');

        if (empty($token)) {
            $this->error('TELEGRAM_BOT_TOKEN belum diatur di file .env!');
            return Command::FAILURE;
        }

        if (empty($url) || str_contains($url, 'localhost') || str_contains($url, '127.0.0.1')) {
            $this->error('URL webhook harus berupa domain publik HTTPS (bukan localhost)!');
            $this->line('Contoh penggunaan:');
            $this->info('php artisan telegram:set-webhook https://keuangan-kita.vercel.app');
            return Command::FAILURE;
        }

        $fullWebhookUrl = rtrim($url, '/') . '/api/telegram/webhook';
        $this->info("Mendaftarkan webhook ke: {$fullWebhookUrl}");

        $endpoint = "https://api.telegram.org/bot{$token}/setWebhook";
        $payload = [
            'url' => $fullWebhookUrl,
            'drop_pending_updates' => true,
        ];

        if (!empty($secret)) {
            $payload['secret_token'] = $secret;
        }

        try {
            $response = Http::withoutVerifying()->timeout(10)->post($endpoint, $payload);
            $result = $response->json();

            if ($response->successful() && ($result['ok'] ?? false)) {
                $this->info('✅ Webhook Telegram BERHASIL didaftarkan!');
                $this->line("Deskripsi: " . ($result['description'] ?? 'OK'));
                return Command::SUCCESS;
            }

            $this->error('❌ Gagal mendaftarkan webhook: ' . ($result['description'] ?? 'Unknown error'));
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Koneksi ke Telegram API gagal: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
