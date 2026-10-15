# Proposal: Foundations & Vercel Serverless Setup

## 1. Why
Berdasarkan dokumen [BRD-KK-001](file:///s:/Vibe%20Coding/keuangan-kita/docs/brd/BRD-keuangan-kita.md) yang telah disetujui, proyek **Keuangan Kita** membutuhkan fondasi arsitektur dasar sebelum modul Telegram Bot dan Web Analytics dapat dibangun. 

Aplikasi ini akan di-deploy ke **Vercel Serverless** menggunakan runtime PHP (`vercel-php@0.7.3`). Lingkungan serverless Vercel memiliki karakteristik khusus (read-only filesystem, stateless execution, dan routing berbasis function) sehingga konfigurasi platform harus disiapkan sejak awal bersamaan dengan skema database inti.

## 2. What Changes
Proposal ini mencakup implementasi Milestone 1 dari roadmap Keuangan Kita:
1. **Laravel Application Core**:
   - Struktur dasar Laravel 11+ dengan dependensi esensial.
   - Konfigurasi Timezone (`Asia/Jakarta`) dan Currency Locale (IDR).
2. **Vercel Serverless Configuration**:
   - `vercel.json`: Konfigurasi runtime PHP serverless, routing static assets `/build/*` vs backend `api/index.php`.
   - `api/index.php`: Adapter bridge penghubung Vercel Function ke `public/index.php` Laravel dengan auto-creation folder `/tmp/storage`.
   - `AppServiceProvider.php`: Pengalihan runtime storage path ke `/tmp/storage` jika mendeteksi environment Vercel.
   - `.vercelignore`: Pengecualian berkas-berkas dev dan dependensi berat saat deployment.
3. **Core Database Schema & Models**:
   - Migration tabel `users`: Penambahan kolom `telegram_chat_id`, `telegram_username`, `link_token`.
   - Migration tabel `wallets`: Dompet keuangan (`name`, `type`, `balance` tipe `BIGINT`, `is_default`).
   - Migration tabel `categories`: Kategori transaksi (`name`, `type` income/expense, `icon`, `color`).
   - Migration tabel `transactions`: Transaksi finansial (`amount` tipe `BIGINT`, `type`, `notes`, `transacted_at` auto-timestamp).
4. **Data Integrity & Model Security**:
   - Definisi `$fillable` eksplisit pada seluruh model (tidak ada `$guarded = []`).
   - Strict attribute casting (`amount` => `integer`, `transacted_at` => `datetime`).

## 3. Impact & Risk Analysis
- **Impact**: Menyediakan scaffolding yang siap dijalankan secara lokal (`php artisan serve`) maupun di-deploy langsung ke Vercel tanpa error *read-only filesystem*.
- **Risks & Mitigation**:
  - *Risk*: Laravel mencoba menulis log atau compiled view ke direktori `/storage` yang terkunci di Vercel.
  - *Mitigation*: Mengalihkan `APP_CONFIG_CACHE`, `VIEW_COMPILED_PATH`, dan `app()->useStoragePath('/tmp/storage')` secara dinamis.
  - *Risk*: Rounding error pada kalkulasi finansial.
  - *Mitigation*: Menggunakan satuan `BIGINT` (Rupiah utuh) sesuai aturan skill `secure-crud-sanitizer`.
