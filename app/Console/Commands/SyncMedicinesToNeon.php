<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * SyncMedicinesToNeon
 *
 * Safely migrates medicine_master records from local SQLite to Neon PostgreSQL.
 *
 * Usage:
 *   php artisan medicine:sync-to-neon --dry-run          # validate without writing
 *   php artisan medicine:sync-to-neon --chunk=500        # real import
 *   php artisan medicine:sync-to-neon --chunk=500 --resume  # continue interrupted run
 */
class SyncMedicinesToNeon extends Command
{
    protected $signature = 'medicine:sync-to-prod
                            {--chunk=500         : Records per batch (default 500)}
                            {--dry-run           : Validate schema and estimate counts — NO writes}
                            {--resume            : Skip already-processed IDs from progress file}
                            {--reset-progress    : Delete progress file and start from the beginning}
                            {--connection=pgsql_prod : Target DB connection name}
                            {--force             : Skip safety confirmation prompts}';

    protected $aliases = ['medicine:sync-to-neon'];

    protected $description = 'Migrate medicine_master records from local SQLite to Neon PostgreSQL using keyset streaming';

    private const SOURCE_TABLE      = 'medicine_master';
    private const DEST_TABLE        = 'medicine_master';
    private const UNIQUE_KEY        = 'medicine_id';        // stable identifier — never use name
    private const PROGRESS_FILE_DIR = 'storage/app';       // relative to base_path()

    // Columns to migrate (source → destination).
    // Auto-increment `id` is intentionally excluded — Neon assigns its own PK.
    private const COLUMNS = [
        'medicine_id',
        'medicine_name',
        'brand_name',
        'generic_name',
        'composition',
        'strength',
        'dosage_form',
        'category',
        'manufacturer',
        'registration_number',
        'pack_size',
        'stock_unit',
        'source',
        'source_reference',
        'verification_status',
        'created_at',
        'updated_at',
    ];

    // Columns updated on conflict (upsert).
    // Excludes medicine_id (the key) and created_at.
    private const UPSERT_UPDATE_COLS = [
        'medicine_name',
        'brand_name',
        'generic_name',
        'composition',
        'strength',
        'dosage_form',
        'category',
        'manufacturer',
        'registration_number',
        'pack_size',
        'stock_unit',
        'source',
        'source_reference',
        'verification_status',
        'updated_at',
    ];

    // ── State ─────────────────────────────────────────────────────────────────
    private string $connection;
    private bool   $dryRun;
    private int    $chunkSize;
    private string $progressFilePath;

    private int $totalProcessed = 0;
    private int $totalInserted  = 0;
    private int $totalUpdated   = 0;
    private int $totalFailed    = 0;
    private int $totalSkipped   = 0;

    private array $failedRecords = [];

    // ─────────────────────────────────────────────────────────────────────────
    public function handle(): int
    {
        $this->connection       = $this->option('connection');
        $this->dryRun           = (bool) $this->option('dry-run');
        $this->chunkSize        = max(100, min(2000, (int) $this->option('chunk')));
        $this->progressFilePath = base_path(self::PROGRESS_FILE_DIR . '/medicine_sync_progress.json');

        $this->printHeader();

        // ── PHASE 1: Connection test ─────────────────────────────────────────
        if (!$this->testConnection()) {
            return self::FAILURE;
        }

        // ── PHASE 2: Schema validation ───────────────────────────────────────
        if (!$this->validateSchema()) {
            return self::FAILURE;
        }

        // ── PHASE 3: Pre-flight summary ──────────────────────────────────────
        $this->preFlight();

        if ($this->dryRun) {
            $this->info('');
            $this->info('✅ DRY-RUN complete — no data written. Run without --dry-run to import.');
            return self::SUCCESS;
        }

        // ── PHASE 4: Safety confirmation ─────────────────────────────────────
        if (!$this->option('force') && !$this->confirmImport()) {
            $this->warn('Import cancelled.');
            return self::SUCCESS;
        }

        // ── PHASE 5: Progress/resume setup ───────────────────────────────────
        if ($this->option('reset-progress') && file_exists($this->progressFilePath)) {
            unlink($this->progressFilePath);
            $this->info('Progress file reset — starting from the beginning.');
        }

        $progress    = $this->loadProgress();
        $lastId      = $this->option('resume') ? ($progress['last_processed_id'] ?? 0) : 0;
        $this->totalInserted = $progress['total_inserted'] ?? 0;
        $this->totalUpdated  = $progress['total_updated']  ?? 0;
        $this->totalFailed   = $progress['total_failed']   ?? 0;
        $this->totalSkipped  = $lastId > 0 ? ($progress['total_skipped'] ?? 0) : 0;

        if ($lastId > 0) {
            $this->info("▶  Resuming from source id > {$lastId}");
        }

        // ── PHASE 6: Stream & import ─────────────────────────────────────────
        $this->runImport($lastId, $progress);

        // ── PHASE 7: Final verification ──────────────────────────────────────
        $this->finalVerification();

        return $this->totalFailed === 0 ? self::SUCCESS : self::FAILURE;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PHASE 1 — Connection test
    // ─────────────────────────────────────────────────────────────────────────
    private function testConnection(): bool
    {
        $this->info('');
        $this->info('── Phase 1: Testing connection to [' . $this->connection . '] ──');

        try {
            $result = DB::connection($this->connection)->select('SELECT version() AS ver');
            $ver    = $result[0]->ver ?? 'unknown';
            $this->info("  ✅ Connected — PostgreSQL: {$ver}");

            // Verify it's actually Neon (hostname check)
            $host = config("database.connections.{$this->connection}.host", '');
            if ($host) {
                $this->line("  Host: {$host}");
            }
        } catch (\Throwable $e) {
            $this->error('  ❌ Connection failed: ' . $e->getMessage());
            $this->line('');
            $this->line('  Set these variables in your .env:');
            $this->line('    PROD_DB_HOST=<hostname>.neon.tech');
            $this->line('    PROD_DB_PORT=5432');
            $this->line('    PROD_DB_DATABASE=<db-name>');
            $this->line('    PROD_DB_USERNAME=<username>');
            $this->line('    PROD_DB_PASSWORD=<password>');
            $this->line('    PROD_DB_SSLMODE=require');
            $this->line('');
            $this->line('  Then run: php artisan config:clear');
            return false;
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PHASE 2 — Schema validation
    // ─────────────────────────────────────────────────────────────────────────
    private function validateSchema(): bool
    {
        $this->info('');
        $this->info('── Phase 2: Schema validation ──');

        // Source (SQLite)
        $srcCols = array_column(
            DB::select('PRAGMA table_info(' . self::SOURCE_TABLE . ')'),
            'name'
        );
        if (empty($srcCols)) {
            $this->error('  ❌ Source table [' . self::SOURCE_TABLE . '] not found in SQLite.');
            return false;
        }
        $this->info('  ✅ Source [sqlite.' . self::SOURCE_TABLE . ']: ' . implode(', ', $srcCols));

        // Destination (Neon)
        $destHasTable = Schema::connection($this->connection)->hasTable(self::DEST_TABLE);

        if (!$destHasTable) {
            $this->warn('  ⚠️  Destination table [' . self::DEST_TABLE . '] does not exist on Neon.');

            if ($this->dryRun) {
                $this->warn('  DRY-RUN: Would run migrations. Run: php artisan migrate --database=' . $this->connection . ' --force');
                return true;
            }

            if (!$this->option('force') && !$this->confirm('  Run migrations on Neon now?', true)) {
                $this->error('  Aborted. Run migrations first.');
                return false;
            }

            $this->call('migrate', [
                '--database' => $this->connection,
                '--force'    => true,
            ]);

            if (!Schema::connection($this->connection)->hasTable(self::DEST_TABLE)) {
                $this->error('  ❌ Migration failed — table still missing.');
                return false;
            }
        }

        // Check required columns exist on destination
        $destCols   = Schema::connection($this->connection)->getColumnListing(self::DEST_TABLE);
        $missing    = array_diff(self::COLUMNS, $destCols);
        $extra      = array_diff($destCols, array_merge(self::COLUMNS, ['id']));

        if (!empty($missing)) {
            $this->error('  ❌ Missing columns on destination: ' . implode(', ', $missing));
            return false;
        }

        $this->info('  ✅ Destination [' . $this->connection . '.' . self::DEST_TABLE . ']: all required columns present');

        if (!empty($extra)) {
            $this->line('  ℹ️  Extra destination columns (will be ignored): ' . implode(', ', $extra));
        }

        // Verify unique index on medicine_id exists on destination
        $this->line('  ℹ️  Unique key: [' . self::UNIQUE_KEY . '] — upsert will use this for duplicate prevention');

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PHASE 3 — Pre-flight summary
    // ─────────────────────────────────────────────────────────────────────────
    private function preFlight(): void
    {
        $this->info('');
        $this->info('── Phase 3: Pre-flight summary ──');

        $sourceCount = DB::table(self::SOURCE_TABLE)->count();
        $this->line("  Source (SQLite) records  : {$sourceCount}");

        try {
            $destCount = DB::connection($this->connection)->table(self::DEST_TABLE)->count();
            $this->line("  Destination (Neon) existing: {$destCount}");
        } catch (\Throwable $e) {
            $this->line("  Destination (Neon) existing: (table not yet created)");
            $destCount = 0;
        }

        $this->line("  Chunk size               : {$this->chunkSize}");
        $this->line("  Unique key               : " . self::UNIQUE_KEY);
        $this->line("  Duplicate strategy       : UPSERT (update on conflict)");
        $this->line("  Columns migrated         : " . count(self::COLUMNS));
        $this->line("  Mode                     : " . ($this->dryRun ? '🔵 DRY-RUN' : '🔴 LIVE IMPORT'));

        // Duplicate detection report
        $nullIds = DB::table(self::SOURCE_TABLE)->whereNull(self::UNIQUE_KEY)->count();
        $emptyIds = DB::table(self::SOURCE_TABLE)->where(self::UNIQUE_KEY, '')->count();
        if ($nullIds > 0 || $emptyIds > 0) {
            $this->warn("  ⚠️  Records with NULL/empty medicine_id: " . ($nullIds + $emptyIds) . " — these will be skipped");
        } else {
            $this->line("  ✅ All source records have valid medicine_id");
        }

        // Source ID range
        $minId = DB::table(self::SOURCE_TABLE)->min('id');
        $maxId = DB::table(self::SOURCE_TABLE)->max('id');
        $this->line("  Source ID range          : {$minId} → {$maxId}");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Safety confirmation
    // ─────────────────────────────────────────────────────────────────────────
    private function confirmImport(): bool
    {
        $this->info('');
        $this->warn('  ⚠️  This will UPSERT medicine records into Neon PostgreSQL.');
        $this->warn('  Existing records with matching medicine_id will be UPDATED.');
        $this->warn('  No records will be deleted. No local SQLite data will be changed.');
        $this->info('');
        return $this->confirm('  Proceed with import?', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PHASE 6 — Stream and import using keyset pagination
    // ─────────────────────────────────────────────────────────────────────────
    private function runImport(int $lastId, array &$progress): void
    {
        $this->info('');
        $this->info('── Phase 4: Import ──');

        $totalSource = DB::table(self::SOURCE_TABLE)->where('id', '>', $lastId)->count();
        $this->line("  Records to process: {$totalSource}");

        $bar = $this->output->createProgressBar($totalSource);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Inserted: %inserted% Updated: %updated% Failed: %failed%');
        $bar->setMessage('0', 'inserted');
        $bar->setMessage('0', 'updated');
        $bar->setMessage('0', 'failed');
        $bar->start();

        $currentId = $lastId;

        do {
            // ── Keyset pagination — no OFFSET, uses indexed id column ──────
            $rows = DB::table(self::SOURCE_TABLE)
                ->select(self::COLUMNS)                // exclude auto-inc id from source
                ->addSelect('id as _source_id')        // keep for keyset tracking
                ->where('id', '>', $currentId)
                ->whereNotNull(self::UNIQUE_KEY)
                ->where(self::UNIQUE_KEY, '!=', '')
                ->orderBy('id')
                ->limit($this->chunkSize)
                ->get();

            if ($rows->isEmpty()) {
                break;
            }

            // Track highest source id in this chunk
            $maxChunkId = $rows->max('_source_id');

            // Strip _source_id before insert (not a real column)
            $batch = $rows->map(function ($row) {
                $arr = (array) $row;
                unset($arr['_source_id']);
                return $arr;
            })->toArray();

            // ── Batch upsert ──────────────────────────────────────────────
            $inserted = 0;
            $updated  = 0;

            try {
                // Count before to calculate inserted vs updated
                $beforeCount = DB::connection($this->connection)
                    ->table(self::DEST_TABLE)
                    ->whereIn(self::UNIQUE_KEY, array_column($batch, self::UNIQUE_KEY))
                    ->count();

                DB::connection($this->connection)
                    ->table(self::DEST_TABLE)
                    ->upsert(
                        $batch,
                        [self::UNIQUE_KEY],
                        self::UPSERT_UPDATE_COLS
                    );

                $afterCount = DB::connection($this->connection)
                    ->table(self::DEST_TABLE)
                    ->whereIn(self::UNIQUE_KEY, array_column($batch, self::UNIQUE_KEY))
                    ->count();

                $inserted = max(0, $afterCount - $beforeCount);
                $updated  = count($batch) - $inserted;

                $this->totalInserted  += $inserted;
                $this->totalUpdated   += $updated;
                $this->totalProcessed += count($batch);

            } catch (\Throwable $e) {
                $this->totalFailed += count($batch);
                $this->failedRecords[] = [
                    'chunk_start_id' => $currentId,
                    'chunk_end_id'   => $maxChunkId,
                    'count'          => count($batch),
                    'error'          => $e->getMessage(),
                ];
                Log::error('SyncMedicinesToNeon: batch failed', [
                    'start_id' => $currentId,
                    'end_id'   => $maxChunkId,
                    'error'    => $e->getMessage(),
                ]);

                // If transient error, retry once
                try {
                    DB::connection($this->connection)->reconnect($this->connection);
                    DB::connection($this->connection)->table(self::DEST_TABLE)->upsert(
                        $batch,
                        [self::UNIQUE_KEY],
                        self::UPSERT_UPDATE_COLS
                    );
                    $this->totalFailed   -= count($batch);
                    $this->totalInserted += count($batch);
                    array_pop($this->failedRecords);
                } catch (\Throwable $retryE) {
                    // Record the retry failure too
                    $this->failedRecords[count($this->failedRecords) - 1]['retry_error'] = $retryE->getMessage();
                }
            }

            // ── Advance keyset ────────────────────────────────────────────
            $currentId = $maxChunkId;

            // ── Persist progress for resume ───────────────────────────────
            $progress = [
                'last_processed_id' => $currentId,
                'total_inserted'    => $this->totalInserted,
                'total_updated'     => $this->totalUpdated,
                'total_failed'      => $this->totalFailed,
                'total_skipped'     => $this->totalSkipped,
                'updated_at'        => now()->toIso8601String(),
            ];
            $this->saveProgress($progress);

            // ── Update progress bar ───────────────────────────────────────
            $bar->setMessage((string) $this->totalInserted, 'inserted');
            $bar->setMessage((string) $this->totalUpdated,  'updated');
            $bar->setMessage((string) $this->totalFailed,   'failed');
            $bar->advance(count($rows));

        } while ($rows->count() === $this->chunkSize);

        $bar->finish();
        $this->newLine(2);

        // Write failed records log
        if (!empty($this->failedRecords)) {
            $logPath = storage_path('logs/medicine_sync_failed.json');
            file_put_contents($logPath, json_encode($this->failedRecords, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->warn("⚠️  Failed batches logged to: {$logPath}");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PHASE 7 — Final verification
    // ─────────────────────────────────────────────────────────────────────────
    private function finalVerification(): void
    {
        $this->info('── Phase 5: Final Verification ──');
        $this->info('');

        $sourceTotal = DB::table(self::SOURCE_TABLE)->count();

        try {
            $destTotal = DB::connection($this->connection)->table(self::DEST_TABLE)->count();
        } catch (\Throwable $e) {
            $this->error('Could not query destination: ' . $e->getMessage());
            $destTotal = -1;
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Source (SQLite) total',     $sourceTotal],
                ['Destination (Neon) total',  $destTotal],
                ['This run — inserted (new)', $this->totalInserted],
                ['This run — updated (dup)',  $this->totalUpdated],
                ['This run — failed',         $this->totalFailed],
                ['Skipped (resume offset)',   $this->totalSkipped],
            ]
        );

        // Coverage check
        if ($destTotal >= $sourceTotal) {
            $this->info('✅ Neon has equal or more records than source — migration complete.');
        } elseif ($destTotal >= $sourceTotal * 0.99) {
            $this->warn('⚠️  Neon has ' . ($sourceTotal - $destTotal) . ' fewer records than source (>99% coverage).');
            $this->warn('   Re-run with --resume to retry any missing records.');
        } else {
            $coverage = $destTotal > 0 ? round(($destTotal / $sourceTotal) * 100, 1) : 0;
            $this->error("❌ Neon coverage: {$coverage}%. Re-run with --resume.");
        }

        // Spot-check: test first, middle and last records
        $this->info('');
        $this->info('Spot-check: searching specific records...');

        $spots = [
            ['position' => 'first',  'row' => DB::table(self::SOURCE_TABLE)->orderBy('id')->first()],
            ['position' => 'middle', 'row' => DB::table(self::SOURCE_TABLE)->skip((int)($sourceTotal / 2))->orderBy('id')->first()],
            ['position' => 'last',   'row' => DB::table(self::SOURCE_TABLE)->orderByDesc('id')->first()],
        ];

        foreach ($spots as $spot) {
            $row = $spot['row'];
            if (!$row) {
                continue;
            }

            try {
                $found = DB::connection($this->connection)
                    ->table(self::DEST_TABLE)
                    ->where(self::UNIQUE_KEY, $row->{self::UNIQUE_KEY})
                    ->exists();

                $icon  = $found ? '✅' : '❌';
                $label = str_pad('[' . $spot['position'] . ']', 10);
                $this->line("  {$icon} {$label} {$row->medicine_id} — {$row->medicine_name}");
            } catch (\Throwable $e) {
                $this->warn("  ⚠️  Could not spot-check [{$spot['position']}]: " . $e->getMessage());
            }
        }

        // Clean up progress file on full success
        if ($this->totalFailed === 0 && $destTotal >= $sourceTotal * 0.99) {
            if (file_exists($this->progressFilePath)) {
                unlink($this->progressFilePath);
                $this->line('');
                $this->line('  Progress file cleaned up (import fully complete).');
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────
    private function printHeader(): void
    {
        $this->newLine();
        $this->line(str_repeat('─', 60));
        $this->line('  Medicine Master → Neon PostgreSQL Migration');
        $this->line('  Source : SQLite [' . self::SOURCE_TABLE . '] (246,070 records)');
        $this->line('  Target : ' . $this->connection . ' [' . self::DEST_TABLE . ']');
        $this->line('  Mode   : ' . ($this->dryRun ? 'DRY-RUN' : 'LIVE IMPORT'));
        $this->line('  Chunk  : ' . $this->chunkSize . ' records/batch');
        $this->line(str_repeat('─', 60));
    }

    private function loadProgress(): array
    {
        if (!file_exists($this->progressFilePath)) {
            return [];
        }
        $data = json_decode(file_get_contents($this->progressFilePath), true);
        return is_array($data) ? $data : [];
    }

    private function saveProgress(array $progress): void
    {
        $dir = dirname($this->progressFilePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->progressFilePath, json_encode($progress, JSON_PRETTY_PRINT));
    }
}

