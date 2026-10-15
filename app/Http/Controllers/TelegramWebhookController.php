<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming webhook updates from Telegram Bot API.
     */
    public function handle(Request $request, TelegramBotService $botService): JsonResponse
    {
        $update = $request->all();

        try {
            if (isset($update['message'])) {
                $botService->handleMessage($update['message']);
            } elseif (isset($update['callback_query'])) {
                $botService->handleCallbackQuery($update['callback_query']);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        // Selalu kembalikan respon HTTP 200 OK secara instan (< 1s)
        return response()->json(['status' => 'ok']);
    }
}
