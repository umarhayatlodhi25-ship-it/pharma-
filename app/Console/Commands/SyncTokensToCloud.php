<?php

namespace App\Console\Commands;

use App\Models\SyncOutbox;
use App\Services\TokenSyncService;
use Illuminate\Console\Command;

class SyncTokensToCloud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:sync-to-cloud {--chunk=100 : Number of records to process per batch} {--retry-failed : Reset failed outbox items back to pending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize local offline-generated OPD tokens and call logs to the cloud database';

    /**
     * Execute the console command.
     */
    public function handle(TokenSyncService $syncService): int
    {
        $this->info('Starting OPD Token Cloud Synchronization...');

        if ($this->option('retry-failed')) {
            $resetCount = SyncOutbox::where('status', 'failed')->update([
                'status'   => 'pending',
                'attempts' => 0,
            ]);
            $this->info("Reset {$resetCount} previously failed records back to pending.");
        }

        if (!$syncService->isCloudReachable()) {
            $this->warn('Cloud database (pgsql_prod) is currently unreachable. Local tokens remain safely stored in local database.');
            return self::FAILURE;
        }

        $chunk = (int) $this->option('chunk');
        if ($chunk <= 0) {
            $chunk = 100;
        }

        $pendingCount = SyncOutbox::where('status', 'pending')->count();
        if ($pendingCount === 0) {
            $this->info('No pending tokens or events to synchronize. All records are up to date.');
            return self::SUCCESS;
        }

        $this->info("Found {$pendingCount} pending outbox event(s). Processing in batches of {$chunk}...");
        $bar = $this->output->createProgressBar($pendingCount);
        $bar->start();

        $totalSynced = 0;
        $totalFailed = 0;

        while (true) {
            $result = $syncService->syncPendingOutbox($chunk);
            if ($result['processed'] === 0) {
                break;
            }

            $totalSynced += $result['synced'];
            $totalFailed += $result['failed'];
            $bar->advance($result['processed']);

            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $err) {
                    $this->newLine();
                    $this->error("  " . $err);
                }
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Successfully Synced', $totalSynced],
                ['Failed', $totalFailed],
                ['Remaining Pending', SyncOutbox::where('status', 'pending')->count()],
            ]
        );

        $this->info('Synchronization routine completed.');
        return self::SUCCESS;
    }
}

