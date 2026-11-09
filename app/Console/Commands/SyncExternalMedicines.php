<?php

namespace App\Console\Commands;

use App\Jobs\SyncMedicineChunkJob;
use App\Services\ExternalMedicineApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncExternalMedicines extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'medicines:sync-external
        {--query= : Optional query string to filter medicines}
        {--limit=200 : Number of records per API page (e.g. 50, 100, 200)}
        {--start-page=1 : Page number to start fetching from}
        {--max-pages= : Maximum number of pages to process (leave empty to fetch all)}
        {--target=master : Target database table: "master" (medicine_master), "operational" (medicines), or "both"}
        {--status=verified : Verification status for master records: verified, pending, rejected}
        {--delay=0 : Delay in milliseconds between page requests to avoid rate limits}
        {--queue : Dispatch pages as background queue jobs instead of running synchronously}';

    /**
     * The console command description.
     */
    protected $description = 'Sync external medicine API records (supporting 80,000+ items) into medicine_master and/or medicines';

    /**
     * Execute the console command.
     */
    public function handle(ExternalMedicineApiService $apiService): int
    {
        $query = (string) ($this->option('query') ?? '');
        $limit = max(10, (int) $this->option('limit'));
        $startPage = max(1, (int) $this->option('start-page'));
        $maxPages = $this->option('max-pages') !== null ? (int) $this->option('max-pages') : null;
        $target = strtolower((string) ($this->option('target') ?? 'master'));
        $status = strtolower((string) ($this->option('status') ?? 'verified'));
        $delayMs = max(0, (int) $this->option('delay'));
        $useQueue = (bool) $this->option('queue');

        $this->info("=== External Medicine API Synchronization ===");
        $this->line("Target: <comment>{$target}</comment> | Batch Size: <comment>{$limit}</comment> | Start Page: <comment>{$startPage}</comment>");
        if ($query !== '') {
            $this->line("Query Filter: <comment>{$query}</comment>");
        }

        // Test connectivity and fetch first page
        $this->info("Connecting to external Medicine API...");
        try {
            $firstPage = $apiService->fetchPage($startPage, $limit, $query);
        } catch (\Throwable $e) {
            $this->error("Failed to connect to external API: " . $e->getMessage());
            $this->line("Please verify MEDICINE_API_BASE_URL and MEDICINE_API_KEY in your .env file.");
            return Command::FAILURE;
        }

        $totalRecords = $firstPage['total'] ?? 0;
        $totalPages = $firstPage['pages'] ?? 1;

        if ($maxPages !== null) {
            $totalPages = min($totalPages, ($startPage + $maxPages - 1));
        }

        $pagesToProcess = max(1, $totalPages - $startPage + 1);

        $this->info("External Catalog: <info>{$totalRecords}</info> total records across <info>{$firstPage['pages']}</info> pages.");
        $this->info("Pages to process in this run: <info>{$pagesToProcess}</info> (Pages {$startPage} to {$totalPages})");

        // If --queue flag passed, dispatch queue jobs
        if ($useQueue) {
            $this->info("Dispatching chunk jobs to Laravel queue...");
            $bar = $this->output->createProgressBar($pagesToProcess);
            $bar->start();

            for ($page = $startPage; $page <= $totalPages; $page++) {
                SyncMedicineChunkJob::dispatch($page, $limit, $query, $target, $status);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("Successfully dispatched {$pagesToProcess} jobs to the queue. Run 'php artisan queue:work' to process.");
            return Command::SUCCESS;
        }

        // Synchronous Processing with Progress Bar
        $bar = $this->output->createProgressBar($pagesToProcess);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- Elapsed: %elapsed:6s% -- ETA: %estimated:-6s%');
        $bar->start();

        $startTime = microtime(true);
        $totalReceived = 0;
        $totalMasterUpserted = 0;
        $totalOperationalCreated = 0;
        $failedPages = 0;

        for ($page = $startPage; $page <= $totalPages; $page++) {
            try {
                // For page 1, use already fetched data if page == startPage
                if ($page === $startPage && !empty($firstPage['data'])) {
                    $pageData = $firstPage;
                } else {
                    $pageData = $apiService->fetchPage($page, $limit, $query);
                }

                $items = $pageData['data'] ?? [];
                $count = count($items);
                $totalReceived += $count;

                if ($count === 0) {
                    $bar->advance();
                    break;
                }

                // 1. Sync to medicine_master
                if (in_array($target, ['master', 'both'], true)) {
                    $upserted = $apiService->upsertChunk($items, $status);
                    $totalMasterUpserted += $upserted;
                }

                // 2. Sync to operational medicines table if requested
                if (in_array($target, ['operational', 'both'], true)) {
                    foreach ($items as $item) {
                        try {
                            $apiService->promoteToOperational($item);
                            $totalOperationalCreated++;
                        } catch (\Throwable $e) {
                            Log::warning("SyncExternalMedicines: failed to promote item: " . $e->getMessage());
                        }
                    }
                }

                $bar->advance();

                if ($delayMs > 0 && $page < $totalPages) {
                    usleep($delayMs * 1000);
                }
            } catch (\Throwable $e) {
                $failedPages++;
                Log::error("SyncExternalMedicines: Page {$page} failed: " . $e->getMessage());
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $duration = round(microtime(true) - $startTime, 2);
        $speed = $duration > 0 ? round($totalReceived / $duration, 1) : 0;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Pages Processed', $pagesToProcess - $failedPages],
                ['Pages Failed', $failedPages],
                ['Total Items Received', number_format($totalReceived)],
                ['Upserted to medicine_master', number_format($totalMasterUpserted)],
                ['Promoted to Operational (medicines)', number_format($totalOperationalCreated)],
                ['Duration', "{$duration} seconds"],
                ['Throughput Speed', "{$speed} items/sec"],
            ]
        );

        $this->info("Medicine synchronization completed successfully!");
        return Command::SUCCESS;
    }
}
