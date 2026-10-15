<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GetTelegramWebhookInfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Melihat informasi status webhook Telegram bot saat ini';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $token = config('services.telegram.bot_token');

        if (empty($token)) {
            $this->error('TELEGRAM_BOT_TOKEN belum diatur di file .env!');
            return Command::FAILURE;
        }

        try {
            $response = Http::withoutVerifying()->timeout(10)->get("https://api.telegram.org/bot{$token}/getWebhookInfo");
            $data = $response->json();

            if ($response->successful() && ($data['ok'] ?? false)) {
                $info = $data['result'];
                $this->info('=== INFORMASI STATUS WEBHOOK TELEGRAM ===');
                $this->line('URL Webhook        : ' . ($info['url'] ?: '(Belum diatur / Masih kosong)'));
                $this->line('Pending Updates    : ' . ($info['pending_update_count'] ?? 0));
                $this->line('Custom Certificate : ' . (($info['has_custom_certificate'] ?? false) ? 'Ya' : 'Tidak'));
                if (!empty($info['last_error_date'])) {
                    $this->warn('Error Terakhir     : ' . ($info['last_error_message'] ?? '-'));
                }
                return Command::SUCCESS;
            }

            $this->error('Gagal mengambil info webhook: ' . ($data['description'] ?? 'Error'));
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Koneksi ke Telegram API gagal: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
