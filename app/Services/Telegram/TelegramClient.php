<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramClient
{
    protected ?string $botToken;
    protected string $baseUrl;

    public function __construct(?string $botToken = null)
    {
        $this->botToken = $botToken ?? config('services.telegram.bot_token');
        $this->baseUrl = 'https://api.telegram.org/bot' . $this->botToken;
    }

    /**
     * Kirim pesan teks ke chat Telegram (Format default: HTML).
     */
    public function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null): array
    {
        if (empty($this->botToken)) {
            Log::warning('Telegram bot token not configured. Skipping sendMessage.', ['chat_id' => $chatId]);
            return ['ok' => false, 'description' => 'Bot token not set'];
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        $response = Http::withoutVerifying()->timeout(5)->post("{$this->baseUrl}/sendMessage", $payload);
        return $response->json() ?? [];
    }

    /**
     * Update pesan yang sudah ada (misal setelah tombol Undo diklik).
     */
    public function editMessageText(int|string $chatId, int $messageId, string $text, ?array $replyMarkup = null): array
    {
        if (empty($this->botToken)) {
            return ['ok' => false];
        }

        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        $response = Http::withoutVerifying()->timeout(5)->post("{$this->baseUrl}/editMessageText", $payload);
        return $response->json() ?? [];
    }

    /**
     * Respon callback query dari inline button.
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null, bool $showAlert = false): array
    {
        if (empty($this->botToken)) {
            return ['ok' => false];
        }

        $payload = [
            'callback_query_id' => $callbackQueryId,
            'show_alert' => $showAlert,
        ];

        if ($text) {
            $payload['text'] = $text;
        }

        $response = Http::withoutVerifying()->timeout(5)->post("{$this->baseUrl}/answerCallbackQuery", $payload);
        return $response->json() ?? [];
    }
}
