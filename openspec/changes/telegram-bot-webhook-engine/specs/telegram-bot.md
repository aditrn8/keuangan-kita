# Capability: Telegram Bot Webhook Engine

## Requirements

### Requirement: Telegram Webhook Secret Token Verification
Setiap request ke `POST /api/telegram/webhook` harus memvalidasi header `X-Telegram-Bot-Api-Secret-Token`.

#### Scenario: Secret Token valid
- GIVEN webhook secret dikonfigurasi di `services.telegram.webhook_secret`
- WHEN request datang dengan header `X-Telegram-Bot-Api-Secret-Token` yang cocok
- THEN request diizinkan masuk ke controller dan diproses.

#### Scenario: Secret Token tidak valid
- GIVEN request datang tanpa token atau dengan token salah
- WHEN request melewati middleware
- THEN request ditolak dengan status HTTP 403 Forbidden.

---

### Requirement: Indonesian Amount Parsing
Sistem harus mampu mengekstrak nominal uang dari berbagai format teks percakapan.

#### Scenario: Format singkatan k atau rb
- WHEN user mengetik `50k` atau `50rb` atau `50ribu`
- THEN parser menghasilkan integer `50000`.

#### Scenario: Format juta atau jt
- WHEN user mengetik `1.5jt` atau `2juta`
- THEN parser menghasilkan integer `1500000` dan `2000000`.

#### Scenario: Format angka biasa bertitik
- WHEN user mengetik `75.000`
- THEN parser menghasilkan integer `75000`.

---

### Requirement: Atomic Financial Mutation via Telegram
Pencatatan pengeluaran atau pemasukan lewat bot wajib mengubah saldo dompet pengguna secara atomik.

#### Scenario: Catat pengeluaran `/catat 50000 makan siang`
- GIVEN user memiliki dompet utama dengan saldo Rp 500.000
- WHEN user mengirim `/catat 50000 makan siang`
- THEN transaksi tersimpan dengan tipe `expense`, nominal Rp 50.000, dan timestamp otomatis `now()`
- AND saldo dompet berkurang menjadi Rp 450.000
- AND bot membalas konfirmasi struk dengan tombol Undo.

#### Scenario: Cek saldo `/saldo`
- GIVEN user memiliki dompet BCA (Rp 1.000.000) dan Tunai (Rp 50.000)
- WHEN user mengirim `/saldo`
- THEN bot membalas rincian setiap dompet dan total agregat Rp 1.050.000.

#### Scenario: Batalkan transaksi (Undo Callback)
- GIVEN transaksi baru saja dicatat 2 menit yang lalu
- WHEN user menekan tombol `[🗑 Batalkan Transaksi]`
- THEN saldo dompet dikembalikan ke nilai semula
- AND record transaksi dihapus
- AND pesan Telegram diperbarui menjadi status Dibatalkan.
