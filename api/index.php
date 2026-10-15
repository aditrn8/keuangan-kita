<?php

/**
 * Vercel Serverless PHP Entrypoint
 * Keuangan Kita
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

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

// Normalisasi SCRIPT_NAME untuk routing Laravel di serverless environment
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Maintenance mode check jika ada
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoload Composer dependencies
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel Application
/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Request::capture());
