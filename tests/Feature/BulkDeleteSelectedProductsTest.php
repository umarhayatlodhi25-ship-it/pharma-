<?php

namespace Tests\Feature;

use App\Livewire\Admin\AddMedicine;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\ProductAuditLog;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ProductBulkDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BulkDeleteSelectedProductsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $pharmacist;
    protected Category $category;

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
    }

    /**
     * Test 1: Service calculates accurate preview statistics and time estimates for selected IDs.
     */
    public function test_service_returns_preview_stats_and_time_estimates_for_ids(): void
    {
        $med1 = Medicine::create(['name' => 'Unreferenced 1', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 10]);
        $med2 = Medicine::create(['name' => 'Unreferenced 2', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 15]);
        $med3 = Medicine::create(['name' => 'With Sales History', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 20]);

        // Link med3 to a sale
        $batch = MedicineBatch::create([
            'medicine_id' => $med3->id,
            'batch_number' => 'BATCH-TEST-001',
            'expiry_date' => now()->addYear(),
            'quantity' => 10,
            'purchase_price' => 10,
            'selling_price' => 20,
        ]);
        $customer = Customer::create(['name' => 'Test Customer', 'phone' => '03001234567']);
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-001',
            'subtotal' => 100,
            'total_amount' => 100,
            'paid_amount' => 100,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'user_id' => $this->admin->id,
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'medicine_id' => $med3->id,
            'batch_id' => $batch->id,
            'quantity' => 5,
            'unit_price' => 20,
            'subtotal' => 100,
        ]);

        $service = app(ProductBulkDeletionService::class);
        $stats = $service->getPreviewStatsForIds([$med1->id, $med2->id, $med3->id]);

        $this->assertEquals(3, $stats['total_products']);
        $this->assertEquals(1, $stats['with_history_count']);
        $this->assertEquals(2, $stats['without_history_count']);
        $this->assertNotEmpty($stats['estimated_time_text']);
        $this->assertGreaterThanOrEqual(1, $stats['estimated_seconds']);
    }

    /**
     * Test 2: executeForIds permanently deletes unreferenced items and archives items with history.
     */
    public function test_service_executes_safe_deletion_and_preserves_history(): void
    {
        $med1 = Medicine::create(['name' => 'Safe Purge Product', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 10]);
        $med2 = Medicine::create(['name' => 'Preserved Product', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 20]);

        $batch2 = MedicineBatch::create([
            'medicine_id' => $med2->id,
            'batch_number' => 'BATCH-TEST-002',
            'expiry_date' => now()->addYear(),
            'quantity' => 10,
            'purchase_price' => 15,
            'selling_price' => 25,
        ]);

        $customer = Customer::create(['name' => 'Walk In', 'phone' => '03009999999']);
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-002',
            'subtotal' => 50,
            'total_amount' => 50,
            'paid_amount' => 50,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'user_id' => $this->admin->id,
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'medicine_id' => $med2->id,
            'batch_id' => $batch2->id,
            'quantity' => 2,
            'unit_price' => 25,
            'subtotal' => 50,
        ]);

        $service = app(ProductBulkDeletionService::class);
        $result = $service->executeForIds([$med1->id, $med2->id], $this->admin);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(2, $result['total_processed']);
        $this->assertEquals(1, $result['archived_count']);
        $this->assertEquals(1, $result['deleted_count']);

        // med1 should be completely deleted from DB
        $this->assertDatabaseMissing('medicines', ['id' => $med1->id]);

        // med2 should still exist but marked archived and soft-deleted
        $this->assertDatabaseHas('medicines', ['id' => $med2->id, 'status' => 'archived']);
        $this->assertSoftDeleted('medicines', ['id' => $med2->id]);

        // Sale and SaleItem should remain intact
        $this->assertDatabaseHas('sale_items', ['medicine_id' => $med2->id]);

        // Audit log created
        $this->assertDatabaseHas('product_audit_logs', [
            'action' => 'bulk_delete_selected_products',
            'user_id' => $this->admin->id,
        ]);
    }

    /**
     * Test 3: Livewire confirmBulkDelete opens modal and populates bulkDeletePreview.
     */
    public function test_livewire_confirm_bulk_delete_flow(): void
    {
        $med1 = Medicine::create(['name' => 'Item A', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 10]);
        $med2 = Medicine::create(['name' => 'Item B', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 15]);

        Livewire::actingAs($this->admin)
            ->test(AddMedicine::class)
            ->set('selectedMedicines', [$med1->id, $med2->id])
            ->call('confirmBulkDelete')
            ->assertSet('showBulkDeleteModal', true)
            ->assertSee('Delete Selected Products')
            ->assertSee('Estimated Waiting Time')
            ->assertSee('Total Selected')
            ->call('closeBulkDeleteModal')
            ->assertSet('showBulkDeleteModal', false);
    }

    /**
     * Test 4: Livewire bulkDelete completes operation, resets selection, and closes modal.
     */
    public function test_livewire_bulk_delete_executes_successfully(): void
    {
        $med1 = Medicine::create(['name' => 'Livewire Purge 1', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 10]);
        $med2 = Medicine::create(['name' => 'Livewire Purge 2', 'category_id' => $this->category->id, 'base_unit' => 'Tablet', 'unit_price' => 15]);

        Livewire::actingAs($this->admin)
            ->test(AddMedicine::class)
            ->set('selectedMedicines', [$med1->id, $med2->id])
            ->call('confirmBulkDelete')
            ->assertSet('showBulkDeleteModal', true)
            ->call('bulkDelete')
            ->assertSet('showBulkDeleteModal', false)
            ->assertSet('selectedMedicines', [])
            ->assertSee('Bulk deletion completed');

        $this->assertDatabaseMissing('medicines', ['id' => $med1->id]);
        $this->assertDatabaseMissing('medicines', ['id' => $med2->id]);
    }
}
