<?php

/**
 * Vercel Serverless PHP Adapter Bridge
 * Keuangan Kita
 */

// Pastikan direktori sementara di /tmp tersedia untuk Laravel cache, views, logs, dan sessions
$tmpStorage = [
    '/tmp/views',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
];

foreach ($tmpStorage as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Forward execution ke public/index.php Laravel
require __DIR__ . '/../public/index.php';
