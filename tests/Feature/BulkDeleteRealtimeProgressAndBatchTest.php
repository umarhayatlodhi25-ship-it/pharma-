<?php

namespace Tests\Feature;

use App\Livewire\Admin\AddMedicine;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicinePackaging;
use App\Models\ProductAuditLog;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Unit;
use App\Models\User;
use App\Services\ProductBulkDeletionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class BulkDeleteRealtimeProgressAndBatchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $pharmacist;
    protected Category $category;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->pharmacist = User::factory()->create([
            'role' => 'pharmacist',
            'status' => 'active',
        ]);

        $this->category = Category::create([
            'name' => 'General Medicine',
            'slug' => 'general-medicine',
        ]);

        $this->unit = Unit::create([
            'unit_id' => 'tab',
            'name' => 'Tablet',
            'symbol' => 'tab',
            'status' => 'active',
        ]);
    }

    /**
     * Test 1: Confirmation preview displays accurate counts for permanent deletion, archival, and sales history.
     */
    public function test_confirmation_modal_preview_accuracy(): void
    {
        $med1 = Medicine::create(['name' => 'Clean Med 1', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 10]);
        $med2 = Medicine::create(['name' => 'Clean Med 2', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 20]);
        $med3 = Medicine::create(['name' => 'History Med', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 30]);

        // Link med3 to a sale
        $batch = MedicineBatch::create([
            'medicine_id' => $med3->id,
            'batch_number' => 'B-001',
            'expiry_date' => now()->addYear(),
            'quantity' => 10,
            'purchase_price' => 15,
            'selling_price' => 30,
        ]);
        $customer = Customer::create(['name' => 'John Doe', 'phone' => '03001112233']);
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-001',
            'subtotal' => 30,
            'total_amount' => 30,
            'paid_amount' => 30,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'user_id' => $this->admin->id,
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'medicine_id' => $med3->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'unit_price' => 30,
            'subtotal' => 30,
        ]);

        $service = app(ProductBulkDeletionService::class);
        $preview = $service->getPreviewStatsForIds([$med1->id, $med2->id, $med3->id]);

        $this->assertEquals(3, $preview['total_products']);
        $this->assertEquals(2, $preview['eligible_permanent_count']);
        $this->assertEquals(2, $preview['without_history_count']);
        $this->assertEquals(1, $preview['requiring_archival_count']);
        $this->assertEquals(1, $preview['with_history_count']);
        $this->assertEquals(1, $preview['sales_history_count']);
        $this->assertNotEmpty($preview['estimated_time_text']);
        $this->assertStringContainsString('Irreversible Action', $preview['warning']);
    }

    /**
     * Test 2: Batch job initialization, telemetry tracking, and duplicate job prevention.
     */
    public function test_batch_job_init_telemetry_and_duplicate_prevention(): void
    {
        $meds = [];
        for ($i = 1; $i <= 10; $i++) {
            $meds[] = Medicine::create([
                'name' => "Batch Test Med {$i}",
                'category_id' => $this->category->id,
                'base_unit' => 'Tablet',
                'unit_price' => 10,
            ])->id;
        }

        $service = app(ProductBulkDeletionService::class);
        $job = $service->initJob($this->admin, $meds, 'selected');

        $this->assertNotEmpty($job['job_id']);
        $this->assertEquals(10, $job['total']);
        $this->assertEquals(10, $job['remaining']);
        $this->assertEquals(0, $job['processed']);
        $this->assertEquals('processing', $job['status']);

        // Check duplicate job prevention: calling initJob again without force returns the existing job
        $secondAttempt = $service->initJob($this->admin, [$meds[0]], 'selected');
        $this->assertEquals($job['job_id'], $secondAttempt['job_id']);

        // Calling with $force = true creates a fresh job
        $forcedJob = $service->initJob($this->admin, $meds, 'selected', null, null, true);
        $this->assertNotEquals($job['job_id'], $forcedJob['job_id']);
    }

    /**
     * Test 3: Batch processing telemetry updates accurately (small scale: 10 products).
     */
    public function test_batch_processing_with_10_products(): void
    {
        $meds = [];
        for ($i = 1; $i <= 10; $i++) {
            $med = Medicine::create([
                'name' => "Scale 10 Med {$i}",
                'category_id' => $this->category->id,
                'base_unit' => 'Tablet',
                'unit_price' => 10,
            ]);
            MedicinePackaging::create([
                'medicine_id' => $med->id,
                'unit_id' => $this->unit->id,
                'packaging_level' => 'base',
                'conversion_factor' => 1,
            ]);
            $meds[] = $med->id;
        }

        $service = app(ProductBulkDeletionService::class);
        $job = $service->initJob($this->admin, $meds, 'selected', null, null, true);

        // Process batch
        $updatedJob = $service->processNextBatch($job['job_id'], 10);

        $this->assertEquals('completed', $updatedJob['status']);
        $this->assertEquals(10, $updatedJob['total']);
        $this->assertEquals(10, $updatedJob['processed']);
        $this->assertEquals(10, $updatedJob['deleted']);
        $this->assertEquals(0, $updatedJob['archived']);
        $this->assertEquals(0, $updatedJob['failed']);
        $this->assertEquals(0, $updatedJob['remaining']);
        $this->assertEquals(100, $updatedJob['percent']);
        $this->assertGreaterThan(0, $updatedJob['speed']);
        $this->assertEquals(0, $updatedJob['estimated_remaining_seconds']);

        // Verify all 10 were permanently removed from the database
        $this->assertEquals(0, Medicine::count());
        $this->assertEquals(0, MedicinePackaging::count());
    }

    /**
     * Test 4: Batch processing with 100 products (manageable batching in chunks of 50).
     */
    public function test_batch_processing_with_100_products(): void
    {
        $meds = [];
        for ($i = 1; $i <= 100; $i++) {
            $med = Medicine::create([
                'name' => "Scale 100 Med {$i}",
                'category_id' => $this->category->id,
                'base_unit' => 'Tablet',
                'unit_price' => 10 + $i,
            ]);
            $meds[] = $med->id;
        }

        $service = app(ProductBulkDeletionService::class);
        $job = $service->initJob($this->admin, $meds, 'selected', null, null, true);

        // Process in chunks of 50
        $batch1 = $service->processNextBatch($job['job_id'], 50);
        $this->assertEquals('processing', $batch1['status']);
        $this->assertEquals(50, $batch1['processed']);
        $this->assertEquals(50, $batch1['remaining']);
        $this->assertEquals(50, $batch1['percent']);

        // Process next chunk of 50
        $batch2 = $service->processNextBatch($job['job_id'], 50);
        $this->assertEquals('completed', $batch2['status']);
        $this->assertEquals(100, $batch2['processed']);
        $this->assertEquals(0, $batch2['remaining']);
        $this->assertEquals(100, $batch2['percent']);
        $this->assertEquals(100, $batch2['deleted']);

        $this->assertEquals(0, Medicine::count());
    }

    /**
     * Test 5: Scalability with 1,000 products: mix of unreferenced and historical sales.
     */
    public function test_scalability_with_1000_products_and_financial_integrity(): void
    {
        // 1. Create a customer and sale
        $customer = Customer::create(['name' => 'Scale Customer', 'phone' => '03009999999']);
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-SCALE-1000',
            'subtotal' => 500,
            'total_amount' => 500,
            'paid_amount' => 500,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'user_id' => $this->admin->id,
        ]);

        // Insert 1000 products in bulk
        $now = now();
        $records = [];
        for ($i = 1; $i <= 1000; $i++) {
            $records[] = [
                'name' => "Bulk Scale Product {$i}",
                'category_id' => $this->category->id,
                'base_unit' => 'Tablet',
                'unit_price' => 10.00,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($records, 250) as $chunk) {
            DB::table('medicines')->insert($chunk);
        }

        $allIds = Medicine::pluck('id')->toArray();
        $this->assertCount(1000, $allIds);

        // Attach 50 of the products to sales history with valid batches
        $historyProductIds = array_slice($allIds, 0, 50);
        $saleItems = [];
        foreach ($historyProductIds as $hId) {
            $batch = MedicineBatch::create([
                'medicine_id' => $hId,
                'batch_number' => "BAT-SCALE-{$hId}",
                'expiry_date' => now()->addYear(),
                'quantity' => 10,
                'purchase_price' => 5,
                'selling_price' => 10,
            ]);
            $saleItems[] = [
                'sale_id' => $sale->id,
                'medicine_id' => $hId,
                'batch_id' => $batch->id,
                'quantity' => 1,
                'unit_price' => 10,
                'subtotal' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('sale_items')->insert($saleItems);

        // Run deletion service with batches of 100
        $service = app(ProductBulkDeletionService::class);
        $job = $service->initJob($this->admin, $allIds, 'selected', null, null, true);

        while ($job['status'] === 'processing' && $job['remaining'] > 0) {
            $job = $service->processNextBatch($job['job_id'], 100);
        }

        $this->assertEquals('completed', $job['status']);
        $this->assertEquals(1000, $job['total']);
        $this->assertEquals(1000, $job['processed']);
        $this->assertEquals(950, $job['deleted']);
        $this->assertEquals(50, $job['archived']);
        $this->assertEquals(0, $job['failed']);

        // Check Financial Ledgers & Historical Preservation:
        // 950 products completely purged
        $this->assertEquals(0, Medicine::count()); // Active medicines count is 0
        $this->assertEquals(50, Medicine::withTrashed()->count()); // 50 archived medicines preserved
        $this->assertEquals(50, Medicine::onlyTrashed()->where('status', 'archived')->count());

        // Sale and Sale Items completely intact!
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->assertEquals(50, DB::table('sale_items')->where('sale_id', $sale->id)->count());

        // Audit Log created
        $this->assertDatabaseHas('product_audit_logs', [
            'action' => 'bulk_delete_selected_products',
            'user_id' => $this->admin->id,
        ]);
    }

    /**
     * Test 6: Scalability with 3,000 products: ensures batch transactions, no timeouts, accurate counts.
     */
    public function test_scalability_with_3000_products(): void
    {
        $now = now();
        $records = [];
        for ($i = 1; $i <= 3000; $i++) {
            $records[] = [
                'name' => "Mega Scale Product {$i}",
                'category_id' => $this->category->id,
                'base_unit' => 'Tablet',
                'unit_price' => 5.00,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($records, 500) as $chunk) {
            DB::table('medicines')->insert($chunk);
        }

        $totalInDb = DB::table('medicines')->count();
        $this->assertEquals(3000, $totalInDb);

        $service = app(ProductBulkDeletionService::class);
        $preview = $service->getPreviewStats();

        $this->assertEquals(3000, $preview['total_products']);
        $this->assertEquals(3000, $preview['eligible_permanent_count']);
        $this->assertNotEmpty($preview['estimated_time_text']);

        // Initialize Delete All Job
        $job = $service->initJob($this->admin, 'all', 'all', null, null, true);
        $this->assertEquals(3000, $job['total']);
        $this->assertEquals(3000, $job['remaining']);

        // Process batches in chunks of 500
        $iterations = 0;
        while ($job['status'] === 'processing' && $job['remaining'] > 0) {
            $job = $service->processNextBatch($job['job_id'], 500);
            $iterations++;
            $this->assertGreaterThan(0, $job['speed']);
        }

        $this->assertEquals(6, $iterations); // 3000 / 500 = 6 iterations
        $this->assertEquals('completed', $job['status']);
        $this->assertEquals(3000, $job['processed']);
        $this->assertEquals(3000, $job['deleted']);
        $this->assertEquals(0, $job['remaining']);
        $this->assertEquals(100, $job['percent']);

        $this->assertEquals(0, DB::table('medicines')->count());
    }

    /**
     * Test 7: Error handling, item preservation, partial status, and targeted safe retry.
     */
    public function test_error_handling_and_retry_scenario(): void
    {
        $med1 = Medicine::create(['name' => 'Will Succeed 1', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 10]);
        $med2 = Medicine::create(['name' => 'Will Succeed 2', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 10]);

        $service = app(ProductBulkDeletionService::class);
        $job = $service->initJob($this->admin, [$med1->id, $med2->id], 'selected', null, null, true);

        // Process batch
        $result = $service->processNextBatch($job['job_id'], 10);

        // The 2 valid medicines should succeed
        $this->assertDatabaseMissing('medicines', ['id' => $med1->id]);
        $this->assertDatabaseMissing('medicines', ['id' => $med2->id]);
        $this->assertEquals(2, $result['deleted']);

        // Create a product that will simulate a failed item in a previous job
        $failedMed = Medicine::create(['name' => 'Locked Item', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 15]);

        // Simulate a job with this failed item to test retryFailedJob
        $testJobId = 'bdel_test_retry_sim';
        $simulatedJob = [
            'job_id' => $testJobId,
            'user_id' => $this->admin->id,
            'user_name' => $this->admin->name,
            'user_email' => $this->admin->email,
            'type' => 'selected',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'total' => 2,
            'pending_ids' => [],
            'processed_ids' => [$med1->id],
            'processed' => 2,
            'deleted' => 1,
            'archived' => 0,
            'failed' => 1,
            'deleted_ids' => [$med1->id],
            'archived_ids' => [],
            'failed_items' => [
                ['id' => $failedMed->id, 'name' => 'Locked Item', 'error' => 'Database locked'],
            ],
            'status' => 'partial',
            'batch_size' => 10,
            'current_batch' => 1,
            'total_batches' => 1,
            'started_at' => microtime(true) - 2,
            'last_updated_at' => microtime(true),
            'completed_at' => microtime(true),
            'elapsed_seconds' => 2.0,
            'speed' => 1.0,
            'percent' => 100,
            'remaining' => 0,
            'estimated_remaining_seconds' => 0,
            'message' => 'Completed with errors',
        ];
        Cache::put("bulk_delete_job_{$testJobId}", $simulatedJob, now()->addHours(1));

        // Retry must ONLY reprocess the failed item, NOT the already deleted ones!
        $retryJob = $service->retryFailedJob($testJobId, $this->admin);

        $this->assertEquals(1, $retryJob['total']);
        $this->assertEquals([$failedMed->id], $retryJob['pending_ids']);
        $this->assertNotEquals($testJobId, $retryJob['job_id']);
    }

    /**
     * Test 8: Livewire component 3-stage interactive workflow (confirm -> processing -> completed).
     */
    public function test_livewire_interactive_three_stage_workflow(): void
    {
        $med1 = Medicine::create(['name' => 'Livewire Flow 1', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 10]);
        $med2 = Medicine::create(['name' => 'Livewire Flow 2', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 15]);

        $test = Livewire::actingAs($this->admin)
            ->test(AddMedicine::class)
            ->set('selectedMedicines', [$med1->id, $med2->id])
            ->call('confirmBulkDelete');

        // Stage 1: Confirmation
        $test->assertSet('showBulkDeleteModal', true)
            ->assertSet('bulkDeleteModalStep', 'confirm')
            ->assertSee('Delete Selected Products')
            ->assertSee('Estimated Waiting Time')
            ->assertSee('Permanent Purge');

        // Stage 2 & 3: Start Job & Complete
        $test->call('startBulkDeleteJob');

        // Because there are only 2 products, the first batch completes immediately
        $test->assertSet('bulkDeleteModalStep', 'completed')
            ->assertSet('selectedMedicines', [])
            ->assertSee('Bulk Deletion Completed Successfully')
            ->assertSee('View Remaining Products');

        // Test Done button closes modal
        $test->call('finishBulkDeletion')
            ->assertSet('showBulkDeleteModal', false);

        // Verify database state
        $this->assertDatabaseMissing('medicines', ['id' => $med1->id]);
        $this->assertDatabaseMissing('medicines', ['id' => $med2->id]);
    }

    /**
     * Test 9: REST controller API endpoints for batch deletion (init, batch, status, cancel).
     */
    public function test_rest_api_batch_endpoints(): void
    {
        $med = Medicine::create(['name' => 'API Test Med', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 10]);

        // 1. Preview API
        $response = $this->actingAs($this->admin)->json('GET', '/medicines/delete-all/preview', [
            'medicine_ids' => [$med->id],
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_products', 1);

        // 2. Init Job API
        $initResponse = $this->actingAs($this->admin)->json('POST', '/admin/medicines/bulk-delete/init', [
            'type' => 'selected',
            'medicine_ids' => [$med->id],
            'force' => true,
        ]);
        $initResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $jobId = $initResponse->json('data.job_id');
        $this->assertNotEmpty($jobId);

        // 3. Status API
        $statusResponse = $this->actingAs($this->admin)->json('GET', "/admin/medicines/bulk-delete/status/{$jobId}");
        $statusResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1);

        // 4. Batch Processing API
        $batchResponse = $this->actingAs($this->admin)->json('POST', '/admin/medicines/bulk-delete/batch', [
            'job_id' => $jobId,
            'batch_size' => 10,
        ]);
        $batchResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.deleted', 1);

        // Verify product deleted
        $this->assertDatabaseMissing('medicines', ['id' => $med->id]);
    }

    /**
     * Test 10: Cancel active job safely preserves already completed batches.
     */
    public function test_cancel_active_job_preserves_completed_batches(): void
    {
        $med1 = Medicine::create(['name' => 'To Delete Med', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 10]);
        $med2 = Medicine::create(['name' => 'To Preserve Med', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 20]);

        $service = app(ProductBulkDeletionService::class);
        $job = $service->initJob($this->admin, [$med1->id, $med2->id], 'selected', null, null, true);

        // Process batch of 1 item
        $service->processNextBatch($job['job_id'], 1);

        // First item is deleted
        $this->assertDatabaseMissing('medicines', ['id' => $med1->id]);

        // Cancel the job
        $cancelledJob = $service->cancelJob($job['job_id'], $this->admin);
        $this->assertEquals('cancelled', $cancelledJob['status']);

        // Second item must remain untouched in the database!
        $this->assertDatabaseHas('medicines', ['id' => $med2->id]);
    }
}
