<?php

namespace App\Jobs;

use App\Services\ExternalMedicineApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMedicineChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [5, 15, 30];
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $page,
        public int $limit = 200,
        public string $query = '',
        public string $target = 'master',
        public string $status = 'verified'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ExternalMedicineApiService $apiService): void
    {
        Log::info("SyncMedicineChunkJob: Processing page {$this->page} (limit: {$this->limit}, target: {$this->target})");

        $pageData = $apiService->fetchPage($this->page, $this->limit, $this->query);
        $items = $pageData['data'] ?? [];

        if (empty($items)) {
            Log::info("SyncMedicineChunkJob: Page {$this->page} returned 0 records.");
            return;
        }

        // 1. Sync to medicine_master
        if (in_array($this->target, ['master', 'both'], true)) {
            $upserted = $apiService->upsertChunk($items, $this->status);
            Log::info("SyncMedicineChunkJob: Page {$this->page} upserted {$upserted} records into medicine_master.");
        }

        // 2. Promote to operational medicines if requested
        if (in_array($this->target, ['operational', 'both'], true)) {
            $promoted = 0;
            foreach ($items as $item) {
                try {
                    $apiService->promoteToOperational($item);
                    $promoted++;
                } catch (\Throwable $e) {
                    Log::warning("SyncMedicineChunkJob: Failed promoting item on page {$this->page}: " . $e->getMessage());
                }
            }
            Log::info("SyncMedicineChunkJob: Page {$this->page} promoted {$promoted} records to operational medicines.");
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SyncMedicineChunkJob FAILED for page {$this->page}: " . $exception->getMessage());
    }
}
