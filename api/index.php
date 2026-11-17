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

// ── Database bootstrap ──────────────────────────────────────────────────────
// Priority 1: Neon PostgreSQL via DATABASE_URL (set in Vercel environment vars)
// Priority 2: SQLite fallback (for preview deployments without DB credentials)

$databaseUrl  = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? null);
$dbConnection = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? 'sqlite');
$usePostgres  = !empty($databaseUrl) || $dbConnection === 'pgsql';

if ($usePostgres) {
    // ── Neon PostgreSQL path ──────────────────────────────────────────────
    // Force pgsql so config/database.php picks up DATABASE_URL
    putenv('DB_CONNECTION=pgsql');
    $_ENV['DB_CONNECTION']    = 'pgsql';
    $_SERVER['DB_CONNECTION'] = 'pgsql';

    // Clean Neon URL: strip unsupported options= param that breaks pdo_pgsql
    if ($databaseUrl && strpos($databaseUrl, 'options=') !== false) {
        $cleanUrl = preg_replace('/(\&|\?)options=[^\&]+/', '', $databaseUrl);
        putenv("DATABASE_URL={$cleanUrl}");
        $_ENV['DATABASE_URL']    = $cleanUrl;
        $_SERVER['DATABASE_URL'] = $cleanUrl;
    }

    // Set a dummy SQLITE_DATABASE so config/database.php doesn't warn
    $targetDb = '/tmp/database/database.sqlite';
    putenv("SQLITE_DATABASE={$targetDb}");
    $_ENV['SQLITE_DATABASE'] = $targetDb;

} else {
    // ── SQLite fallback path ──────────────────────────────────────────────
    $targetDb = '/tmp/database/database.sqlite';
    $prodDb   = __DIR__ . '/../database/production.sqlite';
    $sourceDb = __DIR__ . '/../database/database.sqlite';

    $isFreshContainer = !file_exists($targetDb) || filesize($targetDb) === 0 || isset($_GET['reset_db']);

    if ($isFreshContainer) {
        if (file_exists($prodDb) && filesize($prodDb) > 0) {
            @copy($prodDb, $targetDb);
        } elseif (file_exists($sourceDb) && filesize($sourceDb) > 0) {
            @copy($sourceDb, $targetDb);
        } else {
            @touch($targetDb);
        }
    }

    putenv("SQLITE_DATABASE={$targetDb}");
    $_ENV['SQLITE_DATABASE'] = $targetDb;

    if (empty(getenv('DB_DATABASE')) && empty($_ENV['DB_DATABASE'])) {
        putenv("DB_DATABASE={$targetDb}");
        $_ENV['DB_DATABASE'] = $targetDb;
    }
}

// ── Vercel Serverless environment overrides ──────────────────────────────────
putenv('SESSION_DRIVER=cookie');
$_ENV['SESSION_DRIVER']    = 'cookie';
$_SERVER['SESSION_DRIVER'] = 'cookie';

putenv('APP_DEBUG=true');
$_ENV['APP_DEBUG']    = 'true';
$_SERVER['APP_DEBUG'] = 'true';

putenv('CACHE_STORE=array');
$_ENV['CACHE_STORE']    = 'array';
$_SERVER['CACHE_STORE'] = 'array';

$_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';
putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');

putenv('LIVEWIRE_TMP_PATH=/tmp/storage/app/livewire-tmp');
$_ENV['LIVEWIRE_TMP_PATH'] = '/tmp/storage/app/livewire-tmp';

putenv('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=tmp-for-livewire');
$_ENV['LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'] = 'tmp-for-livewire';

if (empty($_ENV['APP_KEY']) && empty(getenv('APP_KEY'))) {
    $_ENV['APP_KEY'] = 'base64:nd/sNgRY/g4eQBVZL0iNa7GJPDz+iAEIna2N+UL8fys=';
    putenv('APP_KEY=base64:nd/sNgRY/g4eQBVZL0iNa7GJPDz+iAEIna2N+UL8fys=');
}

try {
    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Application Error on Vercel:\n";
    echo $e->getMessage() . "\n\n";
    echo $e->getTraceAsString();
}
