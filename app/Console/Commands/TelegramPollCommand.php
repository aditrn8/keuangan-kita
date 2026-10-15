<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramPollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menjalankan bot Telegram di lokal secara live (Long Polling tanpa butuh domain/tunnel)';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $botService): int
    {
        $token = config('services.telegram.bot_token');

        if (empty($token)) {
            $this->error('TELEGRAM_BOT_TOKEN belum diatur di file .env!');
            return Command::FAILURE;
        }

        $this->info("Menghubungkan ke @keuanganaditdanleilybot...");

        // Hapus webhook sementara agar polling getUpdates diizinkan oleh Telegram
        Http::withoutVerifying()->timeout(5)->get("https://api.telegram.org/bot{$token}/deleteWebhook");

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🚀 TELEGRAM BOT AKTIF DI LAPTOP LOKAL (LIVE POLLING)");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("Buka Telegram: https://t.me/keuanganaditdanleilybot");
        $this->line("Ketik chat: /start atau /catat 50000 makan siang");
        $this->warn("Tekan [Ctrl + C] untuk berhenti.\n");

        $offset = 0;

        while (true) {
            try {
                $response = Http::withoutVerifying()->timeout(25)->get("https://api.telegram.org/bot{$token}/getUpdates", [
                    'offset' => $offset,
                    'timeout' => 20,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $updates = $data['result'] ?? [];

                    foreach ($updates as $update) {
                        $offset = $update['update_id'] + 1;

                        if (isset($update['message'])) {
                            $from = $update['message']['from']['first_name'] ?? 'User';
                            $text = $update['message']['text'] ?? '[Media]';
                            $this->line("📩 Pesan masuk dari <fg=cyan>{$from}</>: <fg=yellow>{$text}</>");

                            $botService->handleMessage($update['message']);
                        } elseif (isset($update['callback_query'])) {
                            $from = $update['callback_query']['from']['first_name'] ?? 'User';
                            $dataVal = $update['callback_query']['data'] ?? '';
                            $this->line("🔘 Tombol diklik oleh <fg=cyan>{$from}</>: <fg=green>{$dataVal}</>");

                            $botService->handleCallbackQuery($update['callback_query']);
                        }
                    }
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // Timeout biasa pada long polling, lanjutkan loop
                continue;
            } catch (\Throwable $e) {
                $this->error("Error polling: " . $e->getMessage());
                sleep(2);
            }
        }

        return Command::SUCCESS;
    }
}
