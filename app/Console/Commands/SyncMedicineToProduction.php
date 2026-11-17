<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SyncMedicineToProduction extends Command
{
    protected $signature = 'medicine:sync-to-prod
                            {--source=database/medicine_export : Directory with exported chunk files}
                            {--connection=pgsql : Target DB connection (pgsql, mysql, sqlite)}
                            {--chunk=500 : Insert batch size per upsert call}
                            {--dry-run : Show what would happen without making changes}
                            {--resume : Skip chunks already imported (checks manifest_progress.json)}
                            {--reset-progress : Delete progress file and start fresh}';

    protected $description = 'Import chunked medicine_master JSON exports into production database using upsert';

    private string $progressFile;

    public function handle(): int
    {
        $sourceDir   = base_path($this->option('source'));
        $connection  = $this->option('connection');
        $batchSize   = (int) $this->option('chunk');
        $dryRun      = $this->option('dry-run');
        $resume      = $this->option('resume');

        $this->progressFile = $sourceDir . DIRECTORY_SEPARATOR . 'manifest_progress.json';

        if ($this->option('reset-progress') && file_exists($this->progressFile)) {
            unlink($this->progressFile);
            $this->info('Progress file reset.');
        }

        // ── Validate source dir ──────────────────────────────────────────────
        if (!is_dir($sourceDir)) {
            $this->error("Source directory not found: {$sourceDir}");
            $this->line("Run first:  php artisan medicine:export");
            return self::FAILURE;
        }

        $manifestFile = $sourceDir . DIRECTORY_SEPARATOR . 'manifest.json';
        if (!file_exists($manifestFile)) {
            $this->error("manifest.json not found in {$sourceDir}. Run medicine:export first.");
            return self::FAILURE;
        }

        $manifest = json_decode(file_get_contents($manifestFile), true);
        $chunks   = $manifest['chunks'] ?? [];
        $totalExpected = $manifest['total_records'] ?? 0;

        $this->info("Source: {$sourceDir}");
        $this->info("Target connection: {$connection}");
        $this->info("Expected records: {$totalExpected}");
        $this->info("Chunk files: " . count($chunks));

        if ($dryRun) {
            $this->warn("DRY RUN — no data will be written.");
        }

        // ── Validate connection ──────────────────────────────────────────────
        try {
            DB::connection($connection)->getPdo();
            $this->info("✅ Connected to [{$connection}]");
        } catch (\Throwable $e) {
            $this->error("Cannot connect to [{$connection}]: " . $e->getMessage());
            $this->line("Set DATABASE_URL or DB_* env vars and retry.");
            return self::FAILURE;
        }

        // ── Ensure table exists ──────────────────────────────────────────────
        if (!Schema::connection($connection)->hasTable('medicine_master')) {
            $this->warn("Table 'medicine_master' does not exist on [{$connection}].");
            if (!$dryRun && $this->confirm("Run migrations on [{$connection}] now?", true)) {
                $this->call('migrate', ['--database' => $connection, '--force' => true]);
            } elseif ($dryRun) {
                $this->warn("Would run: php artisan migrate --database={$connection} --force");
            } else {
                $this->error("Aborted. Run migrations first.");
                return self::FAILURE;
            }
        }

        // ── Load progress ────────────────────────────────────────────────────
        $progress = $resume && file_exists($this->progressFile)
            ? json_decode(file_get_contents($this->progressFile), true)
            : ['completed_chunks' => [], 'imported' => 0, 'failed' => 0, 'skipped' => 0];

        // ── Process chunks ───────────────────────────────────────────────────
        $totalImported = $progress['imported'] ?? 0;
        $totalFailed   = $progress['failed'] ?? 0;
        $totalSkipped  = $progress['skipped'] ?? 0;
        $failedLog     = [];

        $bar = $this->output->createProgressBar(count($chunks));
        $bar->start();

        foreach ($chunks as $chunkMeta) {
            $filename  = $chunkMeta['file'];
            $chunkPath = $sourceDir . DIRECTORY_SEPARATOR . $filename;

            // Skip already-processed chunks when resuming
            if ($resume && in_array($filename, $progress['completed_chunks'] ?? [], true)) {
                $totalSkipped += $chunkMeta['count'] ?? 0;
                $bar->advance();
                continue;
            }

            if (!file_exists($chunkPath)) {
                $this->newLine();
                $this->warn("Chunk file missing: {$filename} — skipping");
                $bar->advance();
                continue;
            }

            $records = json_decode(file_get_contents($chunkPath), true);

            if (empty($records)) {
                $bar->advance();
                continue;
            }

            // Normalise rows: strip auto-inc id, keep medicine_id as unique key
            $rows = array_map(function (array $row) {
                unset($row['id']); // let the target DB assign its own PK
                // Ensure required fields
                if (empty($row['medicine_id'])) {
                    $row['medicine_id'] = 'med_' . Str::slug($row['medicine_name'] ?? 'unknown') . '_' . substr(md5(json_encode($row)), 0, 8);
                }
                if (empty($row['verification_status'])) {
                    $row['verification_status'] = 'pending';
                }
                return $row;
            }, $records);

            if ($dryRun) {
                $totalImported += count($rows);
                $progress['completed_chunks'][] = $filename;
                $bar->advance();
                continue;
            }

            // Upsert in batches of $batchSize
            $batches = array_chunk($rows, $batchSize);
            $chunkFailed = 0;

            foreach ($batches as $batch) {
                try {
                    DB::connection($connection)
                        ->table('medicine_master')
                        ->upsert(
                            $batch,
                            ['medicine_id'],            // unique key
                            [                            // columns to update on conflict
                                'medicine_name', 'brand_name', 'generic_name',
                                'composition', 'strength', 'dosage_form',
                                'category', 'manufacturer', 'registration_number',
                                'pack_size', 'stock_unit', 'source', 'source_reference',
                                'verification_status', 'updated_at',
                            ]
                        );
                    $totalImported += count($batch);
                } catch (\Throwable $e) {
                    $chunkFailed += count($batch);
                    $totalFailed  += count($batch);
                    $failedLog[] = [
                        'chunk' => $filename,
                        'error' => $e->getMessage(),
                        'count' => count($batch),
                    ];
                }
            }

            if ($chunkFailed === 0) {
                $progress['completed_chunks'][] = $filename;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // ── Save progress ────────────────────────────────────────────────────
        $progress['imported'] = $totalImported;
        $progress['failed']   = $totalFailed;
        $progress['skipped']  = $totalSkipped;
        file_put_contents($this->progressFile, json_encode($progress, JSON_PRETTY_PRINT));

        // ── Write failed log ─────────────────────────────────────────────────
        if (!empty($failedLog)) {
            $logPath = $sourceDir . DIRECTORY_SEPARATOR . 'failed_records.json';
            file_put_contents($logPath, json_encode($failedLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->warn("Failed records logged to: {$logPath}");
        }

        // ── Verify count on target ───────────────────────────────────────────
        if (!$dryRun) {
            $prodCount = DB::connection($connection)->table('medicine_master')->count();
            $this->info("✅ Import complete!");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Imported/Updated', $totalImported],
                    ['Failed',          $totalFailed],
                    ['Skipped (resume)', $totalSkipped],
                    ['Production total', $prodCount],
                    ['Expected',         $totalExpected],
                ]
            );

            if ($prodCount < $totalExpected * 0.95) {
                $this->warn("⚠️  Production count ({$prodCount}) is less than 95% of expected ({$totalExpected}). Re-run with --resume to retry failed chunks.");
            }
        } else {
            $this->info("[DRY RUN] Would import: {$totalImported} records");
        }

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

