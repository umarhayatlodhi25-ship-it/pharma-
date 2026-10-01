<?php

// Prepare /tmp directories for Vercel Serverless read-only environment
$tmpDirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/bootstrap/cache',
    '/tmp/database',
    '/tmp/storage/app/livewire-tmp',
];

foreach ($tmpDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Determine database driver
$dbConnection = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? null);

$targetDb = '/tmp/database/database.sqlite';
$prodDb = __DIR__ . '/../database/production.sqlite';
$sourceDb = __DIR__ . '/../database/database.sqlite';
$isFreshContainer = !file_exists($targetDb) || filesize($targetDb) === 0 || isset($_GET['reset_db']);
if ($isFreshContainer) {
    if (file_exists($sourceDb) && filesize($sourceDb) > 0) {
        @copy($sourceDb, $targetDb);
    } else {
        @touch($targetDb);
    }
}

// Ensure the SQLITE_DATABASE env var is set for config/database.php
putenv("SQLITE_DATABASE={$targetDb}");
$_ENV['SQLITE_DATABASE'] = $targetDb;

// If no cloud DB connection or explicitly sqlite, set it as the primary DB
if (!$dbConnection || $dbConnection === 'sqlite') {
    if (empty($_ENV['DB_DATABASE']) && empty(getenv('DB_DATABASE'))) {
        $_ENV['DB_DATABASE'] = $targetDb;
        putenv("DB_DATABASE={$targetDb}");
    }
}

if ($dbUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? null)) {
    if (strpos($dbUrl, 'options=') !== false) {
        $cleanUrl = preg_replace('/(&|\?)options=[^&]+/', '', $dbUrl);
        putenv("DATABASE_URL={$cleanUrl}");
        $_ENV['DATABASE_URL'] = $cleanUrl;
        $_SERVER['DATABASE_URL'] = $cleanUrl;
    }
}

// Set environment variables for Vercel Serverless
putenv('SESSION_DRIVER=cookie');
$_ENV['SESSION_DRIVER'] = 'cookie';
$_SERVER['SESSION_DRIVER'] = 'cookie';

putenv('APP_DEBUG=true');
$_ENV['APP_DEBUG'] = 'true';
$_SERVER['APP_DEBUG'] = 'true';

putenv('CACHE_STORE=array');
$_ENV['CACHE_STORE'] = 'array';
$_SERVER['CACHE_STORE'] = 'array';

$_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';
putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');

putenv('LIVEWIRE_TMP_PATH=/tmp/storage/app/livewire-tmp');
$_ENV['LIVEWIRE_TMP_PATH'] = '/tmp/storage/app/livewire-tmp';

putenv('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=tmp-for-livewire');
$_ENV['LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'] = 'tmp-for-livewire';

if (empty($_ENV['APP_KEY'])) {
    $_ENV['APP_KEY'] = 'base64:nd/sNgRY/g4eQBVZL0iNa7GJPDz+iAEIna2N+UL8fys=';
    putenv('APP_KEY=base64:nd/sNgRY/g4eQBVZL0iNa7GJPDz+iAEIna2N+UL8fys=');
}


require __DIR__ . '/../public/index.php';

