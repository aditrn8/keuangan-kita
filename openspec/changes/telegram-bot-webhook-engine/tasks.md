# Tasks: Telegram Bot Webhook Engine

- [x] 1. Middleware Webhook Security <!-- id: 1-webhook-security -->
  - [x] 1.1 Buat `VerifyTelegramWebhookSecret` middleware
- [x] 2. Smart Amount Parser <!-- id: 2-amount-parser -->
  - [x] 2.1 Buat `App\Services\Telegram\AmountParser`
- [x] 3. Telegram API Client <!-- id: 3-telegram-client -->
  - [x] 3.1 Buat `App\Services\Telegram\TelegramClient` untuk sendMessage, editMessageText, answerCallbackQuery
- [x] 4. Telegram Bot Core Service & Handler <!-- id: 4-bot-service -->
  - [x] 4.1 Buat `App\Services\Telegram\TelegramBotService` dengan routing `/start`, `/catat`, `/keluar`, `/masuk`, `/saldo`, `/laporan`, `/help`
  - [x] 4.2 Terapkan `DB::transaction()` & `lockForUpdate()` pada mutasi saldo
  - [x] 4.3 Tambahkan penanganan Callback Query untuk tombol Undo
- [x] 5. Controller & Route <!-- id: 5-controller-route -->
  - [x] 5.1 Buat `TelegramWebhookController`
  - [x] 5.2 Daftarkan route `POST /api/telegram/webhook` di `routes/api.php`
- [x] 6. Testing & Verifikasi <!-- id: 6-testing -->
  - [x] 6.1 Buat `tests/Feature/TelegramWebhookTest.php`
  - [x] 6.2 Jalankan automated tests dan pastikan semua lulus
