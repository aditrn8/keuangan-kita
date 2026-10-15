# Proposal: Telegram Bot Webhook Engine

## 1. Why
Berdasarkan dokumen [BRD-KK-001](file:///s:/Vibe%20Coding/keuangan-kita/docs/brd/BRD-keuangan-kita.md), nilai utama Keuangan Kita adalah pencatatan pengeluaran & pemasukan kilat (< 3 detik) via Telegram Bot serta pengecekan saldo instan sebelum berbelanja.

## 2. What Changes
1. **Telegram Webhook Security**:
   - Middleware `VerifyTelegramWebhookSecret` untuk validasi header `X-Telegram-Bot-Api-Secret-Token`.
2. **Smart Amount Parsing**:
   - `AmountParser` untuk membaca notasi khas Indonesia (`50k`, `50rb`, `50.000`, `1.5jt`).
3. **Core Telegram Bot Engine**:
   - `TelegramClient`: Wrapper HTTP Client untuk Telegram Bot API (`sendMessage`, `editMessageText`, `answerCallbackQuery`).
   - `TelegramBotService`: Router perintah bot (`/start`, `/catat`, `/keluar`, `/masuk`, `/saldo`, `/laporan`, `/help`).
   - Transaksi atomik & concurrency locking `lockForUpdate()` saat mutasi saldo dompet.
   - Fitur tombol *Undo* interaktif (Callback Query) untuk membatalkan transaksi yang salah dicatat.
4. **Webhook Controller & Route**:
   - Endpoint `POST /api/telegram/webhook`.
5. **Feature Tests**:
   - Pengujian lengkap `TelegramWebhookTest` (command parser, balance deduction, undo transaction).
