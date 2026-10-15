<?php

use App\Http\Controllers\TelegramWebhookController;
use App\Http\Middleware\VerifyTelegramWebhookSecret;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Keuangan Kita
|--------------------------------------------------------------------------
*/

Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware(VerifyTelegramWebhookSecret::class);
