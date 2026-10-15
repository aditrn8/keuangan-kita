---
name: vercel-laravel-deployer
description: Configure and deploy modern Laravel applications to Vercel using Serverless PHP runtime. Handles vercel.json configuration, api/index.php bridge, ephemeral filesystem redirection (/tmp), environment variables, database pooling, and Telegram webhook binding.
allowed-tools: ["*"]
metadata:
  author: Keuangan Kita Team
  version: "1.0"
---

# Vercel Laravel Deployer Skill (Serverless PHP Runtime)

Skill ini adalah panduan lengkap dan konfigurasi baku untuk men-deploy aplikasi **Laravel (v11/v12)** dan **Telegram Bot** ke platform **Vercel** menggunakan **Serverless PHP Runtime**.

---

## 1. Tantangan Arsitektur Laravel di Vercel & Solusinya

| Tantangan Vercel Serverless | Masalah di Laravel | Solusi Wajib |
|---|---|---|
| **Read-Only Filesystem** | Laravel gagal menulis compiled views, cache, sessions, dan log ke folder `/storage`. | Arahkan seluruh path cache, session, views, dan log ke direktori `/tmp/` yang bersifat writable. |
| **Single Entry Point** | Vercel Serverless Functions mengeksekusi script per file di folder `api/`. | Buat adapter file bridge `api/index.php` yang mem-bootstrap `public/index.php`. |
| **Stateless Execution** | Session file lokal & persistent database SQLite lokal akan hilang saat function cold start / shutdown. | Gunakan Managed Database cloud (Supabase PostgreSQL / Neon / PlanetScale / Aiven) dan cache database/Redis. |
| **Static Assets (CSS/JS)** | File aset Vite di `public/build/` harus disajikan langsung oleh CDN Vercel tanpa lewat PHP function. | Konfigurasi routing `vercel.json` memisahkan static assets dari dynamic PHP execution. |

---

## 2. Berkas Konfigurasi `vercel.json`

Letakkan berkas `vercel.json` di root direktori proyek:

```json
{
  "version": 2,
  "framework": null,
  "functions": {
    "api/index.php": {
      "runtime": "vercel-php@0.7.3",
      "memory": 1024,
      "maxDuration": 15
    }
  },
  "routes": [
    {
      "src": "/build/(.*)",
      "dest": "/public/build/$1"
    },
    {
      "src": "/favicon.ico",
      "dest": "/public/favicon.ico"
    },
    {
      "src": "/robots.txt",
      "dest": "/public/robots.txt"
    },
    {
      "src": "/(.*)",
      "dest": "/api/index.php"
    }
  ],
  "env": {
    "APP_ENV": "production",
    "APP_DEBUG": "false",
    "APP_CONFIG_CACHE": "/tmp/config.php",
    "APP_EVENTS_CACHE": "/tmp/events.php",
    "APP_PACKAGES_CACHE": "/tmp/packages.php",
    "APP_ROUTES_CACHE": "/tmp/routes.php",
    "APP_SERVICES_CACHE": "/tmp/services.php",
    "VIEW_COMPILED_PATH": "/tmp/views",
    "CACHE_STORE": "database",
    "SESSION_DRIVER": "database",
    "LOG_CHANNEL": "stderr"
  }
}
```

> **Catatan Runtime**: Runtime `vercel-php` dikelola oleh komunitas Vercel (`vercel-community/php` atau `juicyfx/vercel-php`). Pastikan versi PHP yang ditargetkan adalah PHP 8.2 atau PHP 8.3.

---

## 3. Berkas Adapter Bridge: `api/index.php`

Buat folder `api/` di root proyek dan buat file `api/index.php`:

```php
<?php

// Pastikan direktori sementara di /tmp tersedia untuk Laravel cache & views
$tmpStorage = [
    '/tmp/views',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
];

foreach ($tmpStorage as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Teruskan eksekusi ke public/index.php Laravel
require __DIR__ . '/../public/index.php';
```

---

## 4. Penyesuaian `AppServiceProvider.php` untuk Ephemeral Storage

Tambahkan penyesuaian path storage dinamis pada `app/Providers/AppServiceProvider.php`:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Jika berjalan di lingkungan Vercel serverless
        if (isset($_ENV['VERCEL']) || env('APP_ENV') === 'production') {
            $this->app->useStoragePath('/tmp/storage');
        }
    }

    public function boot(): void
    {
        // Konfigurasi tambahan jika diperlukan
    }
}
```

---

## 5. File `.vercelignore`

Cegah file tidak penting ter-upload ke build Vercel agar ukuran deployment tetap kecil:

```text
.git
.github
.env
.env.*
node_modules
tests
docker-compose.yml
Dockerfile
README.md
openspec
.agents
```

---

## 6. Build Script di `package.json`

Pastikan ada build script untuk mengkompilasi aset frontend Vite dan dependensi:

```json
{
  "private": true,
  "type": "module",
  "scripts": {
    "build": "vite build",
    "dev": "vite",
    "vercel-build": "npm run build"
  },
  "devDependencies": {
    "vite": "^5.0.0",
    "laravel-vite-plugin": "^1.0.0"
  }
}
```

---

## 7. Checklist Environment Variables di Vercel Dashboard

Wajib diisi pada **Settings > Environment Variables** di Vercel:

1. `APP_KEY`: String acak 32 karakter (`base64:...`) dari `php artisan key:generate --show`.
2. `APP_URL`: URL Vercel kamu (contoh: `https://keuangan-kita.vercel.app`).
3. Database (PostgreSQL / MySQL Serverless):
   - `DB_CONNECTION`: `pgsql` (direkomendasikan dengan Supabase / Neon)
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
4. Telegram Bot:
   - `TELEGRAM_BOT_TOKEN`: Token rahasia dari @BotFather.
   - `TELEGRAM_WEBHOOK_SECRET`: Token acak untuk verifikasi header webhook.

---

## 8. Menghubungkan Webhook Telegram Pasca-Deploy

Setelah berhasil deploy ke Vercel:

Jalankan perintah cURL atau buka URL berikut di browser untuk mengaktifkan webhook:
```bash
curl -F "url=https://<YOUR-VERCEL-DOMAIN>.vercel.app/api/telegram/webhook" \
     -F "secret_token=<YOUR_TELEGRAM_WEBHOOK_SECRET>" \
     https://api.telegram.org/bot<YOUR_TELEGRAM_BOT_TOKEN>/setWebhook
```

Untuk memverifikasi status webhook:
```bash
curl https://api.telegram.org/bot<YOUR_TELEGRAM_BOT_TOKEN>/getWebhookInfo
```
