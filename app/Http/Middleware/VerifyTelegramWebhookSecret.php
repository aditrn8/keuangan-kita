<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramWebhookSecret
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedSecret = config('services.telegram.webhook_secret');

        // Jika secret dikonfigurasi, validasi header X-Telegram-Bot-Api-Secret-Token
        if (!empty($expectedSecret)) {
            $incomingSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');

            if (!is_string($incomingSecret) || !hash_equals($expectedSecret, $incomingSecret)) {
                return response()->json(['error' => 'Unauthorized webhook secret'], 403);
            }
        }

        return $next($request);
    }
}
