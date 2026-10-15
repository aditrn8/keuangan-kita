# Capability: Foundations & Serverless Architecture

## Requirements

### Requirement: Vercel Serverless Entrypoint & Storage Redirection
Aplikasi harus dapat berjalan di lingkungan Vercel Serverless tanpa melempar exception `ErrorException: file_put_contents(/storage/...): failed to open stream: Read-only file system`.

#### Scenario: Request HTTP masuk di Vercel
- GIVEN aplikasi berjalan di runtime Vercel Serverless (`$_ENV['VERCEL']` terdeteksi atau `APP_ENV=production`)
- WHEN request HTTP diterima oleh endpoint Vercel
- THEN berkas bridge `api/index.php` memastikan subdirektori `/tmp/storage/framework/{views,cache,sessions}` telah dibuat
- AND Laravel menggunakan `/tmp/storage` sebagai base storage path
- AND respon HTTP 200 berhasil dikembalikan ke client tanpa kegagalan penulisan disk.

---

### Requirement: Database Ledger Schema Integrity
Sistem wajib menyimpan seluruh nilai saldo dan transaksi finansial dalam format integer Rupiah murni (`BIGINT`), bukan tipe floating-point.

#### Scenario: Pembuatan dompet baru dengan saldo awal
- GIVEN pengguna membuat dompet baru "BCA" dengan saldo awal Rp 500.000
- WHEN data disimpan ke tabel `wallets`
- THEN kolom `balance` terisi nilai integer `500000`
- AND model `Wallet` memvalidasi bahwa `user_id` merujuk ke pengguna aktif.

#### Scenario: Transaksi baru dengan timestamp otomatis
- GIVEN transaksi baru dicatat oleh pengguna
- WHEN field `transacted_at` tidak disediakan secara eksplisit
- THEN sistem otomatis mengisi `transacted_at` dengan timestamp waktu server terkini (`now()`)
- AND nominal `amount` divalidasi sebagai integer positif lebih dari 0.

---

### Requirement: Model Mass Assignment Protection
Seluruh model finansial (`Wallet`, `Category`, `Transaction`) harus memiliki proteksi mass assignment yang ketat.

#### Scenario: Pencegahan manipulasi user_id lewat payload input
- GIVEN penyerang mencoba mengirimkan atribut `user_id` orang lain pada request pembuatan transaksi
- WHEN request diproses oleh controller
- THEN controller wajib memaksakan `user_id` diambil dari session pengguna yang diautentikasi (`$request->user()->id`).
