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
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeleteAllProductsSecurityAndIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $pharmacist;
    protected User $cashier;
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

        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'status' => 'active',
        ]);

        $this->category = Category::create([
            'name' => 'General Antibiotics',
            'slug' => 'general-antibiotics',
        ]);
    }

    /**
     * Test 1: Unauthenticated user cannot access delete-all endpoint.
     */
    public function test_unauthenticated_user_cannot_access_delete_all_endpoint(): void
    {
        $response = $this->post(route('admin.medicines.delete-all'), [
            'confirmation' => 'DELETE ALL PRODUCTS',
        ]);

        $response->assertRedirect('/login');

        $previewResponse = $this->get(route('admin.medicines.delete-all.preview'));
        $previewResponse->assertRedirect('/login');
    }

    /**
     * Test 2: Non-admin users (pharmacist, cashier) are denied access (403 or redirect with error).
     */
    public function test_non_admin_users_cannot_access_delete_all_endpoint(): void
    {
        // Pharmacist attempt
        $response1 = $this->actingAs($this->pharmacist)->post(route('admin.medicines.delete-all'), [
            'confirmation' => 'DELETE ALL PRODUCTS',
        ]);
        $this->assertTrue(in_array($response1->status(), [302, 403]));
        if ($response1->status() === 302) {
            $response1->assertSessionHas('error');
        }

        // Cashier attempt
        $response2 = $this->actingAs($this->cashier)->post(route('admin.medicines.delete-all'), [
            'confirmation' => 'DELETE ALL PRODUCTS',
        ]);
        $this->assertTrue(in_array($response2->status(), [302, 403]));

        // Check preview restriction
        $preview1 = $this->actingAs($this->pharmacist)->get(route('admin.medicines.delete-all.preview'));
        $this->assertTrue(in_array($preview1->status(), [302, 403]));
    }

    /**
     * Test 3: Delete-all requires exact confirmation phrase 'DELETE ALL PRODUCTS'.
     */
    public function test_delete_all_requires_exact_confirmation_phrase(): void
    {
        Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Panadol 500mg',
            'dosage_unit' => 'Tablet',
            'base_unit' => 'Tablet',
            'unit_price' => 10,
            'purchase_price' => 7,
        ]);

        // Wrong phrase
        $response = $this->actingAs($this->admin)->post(route('admin.medicines.delete-all'), [
            'confirmation' => 'delete all',
        ]);

        $response->assertSessionHasErrors('confirmation');
        $this->assertEquals(1, Medicine::count());

        // Empty phrase
        $response2 = $this->actingAs($this->admin)->post(route('admin.medicines.delete-all'), [
            'confirmation' => '',
        ]);
        $response2->assertSessionHasErrors('confirmation');
        $this->assertEquals(1, Medicine::count());
    }

    /**
     * Test 4: Preview endpoint returns accurate statistics for administrator.
     */
    public function test_preview_endpoint_returns_accurate_statistics(): void
    {
        $med1 = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Unused Product',
            'dosage_unit' => 'Tablet',
            'base_unit' => 'Tablet',
            'unit_price' => 20,
            'purchase_price' => 15,
        ]);

        $med2 = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Sold Product',
            'dosage_unit' => 'Tablet',
            'base_unit' => 'Tablet',
            'unit_price' => 50,
            'purchase_price' => 35,
        ]);

        $batch = MedicineBatch::create([
            'medicine_id' => $med2->id,
            'batch_number' => 'B-001',
            'quantity' => 10,
            'expiry_date' => Carbon::now()->addYear()->toDateString(),
            'purchase_price' => 35,
            'selling_price' => 50,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV-001',
            'user_id' => $this->admin->id,
            'subtotal' => 50,
            'total_amount' => 50,
            'paid_amount' => 50,
            'payment_method' => 'cash',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'medicine_id' => $med2->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'unit_price' => 50,
            'subtotal' => 50,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.medicines.delete-all.preview'));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'total_products' => 2,
                'with_history_count' => 1,
                'without_history_count' => 1,
                'batches_count' => 1,
            ],
        ]);
    }

    /**
     * Test 5: Empty inventory handles gracefully with zero affected.
     */
    public function test_empty_inventory_handles_gracefully(): void
    {
        $this->assertEquals(0, Medicine::count());

        $response = $this->actingAs($this->admin)->post(route('admin.medicines.delete-all'), [
            'confirmation' => 'DELETE ALL PRODUCTS',
        ]);

        $response->assertRedirect('/medicines');
        $response->assertSessionHas('message');

        // Audit log still records operation
        $this->assertDatabaseHas('product_audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'bulk_delete_all_products',
        ]);
    }

    /**
     * Test 6: Unreferenced products with no history are permanently deleted.
     */
    public function test_unreferenced_products_are_permanently_deleted(): void
    {
        $med1 = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Standalone Item 1',
            'dosage_unit' => 'Tablet',
            'base_unit' => 'Tablet',
            'unit_price' => 10,
            'purchase_price' => 5,
        ]);

        $med2 = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Standalone Item 2',
            'dosage_unit' => 'Syrup',
            'base_unit' => 'Bottle',
            'unit_price' => 120,
            'purchase_price' => 90,
        ]);

        $this->assertEquals(2, Medicine::count());

        $response = $this->actingAs($this->admin)->post(route('admin.medicines.delete-all'), [
            'confirmation' => 'DELETE ALL PRODUCTS',
        ]);

        $response->assertRedirect('/medicines');

        // Both should be permanently deleted (not even in withTrashed)
        $this->assertEquals(0, Medicine::withTrashed()->count());
    }

    /**
     * Test 7: Products linked to sales are archived/soft-deleted, and invoices remain intact.
     */
    public function test_products_linked_to_sales_are_archived_and_historical_invoices_preserved(): void
    {
        $med = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Amoxicillin 500mg',
            'dosage_unit' => 'Capsule',
            'base_unit' => 'Capsule',
            'unit_price' => 15,
            'purchase_price' => 10,
        ]);

        $batch = MedicineBatch::create([
            'medicine_id' => $med->id,
            'batch_number' => 'AMX-99',
            'quantity' => 50,
            'expiry_date' => Carbon::now()->addYear()->toDateString(),
            'purchase_price' => 10,
            'selling_price' => 15,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV-HIST-101',
            'user_id' => $this->admin->id,
            'subtotal' => 30,
            'total_amount' => 30,
            'paid_amount' => 30,
            'payment_method' => 'cash',
        ]);

        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'medicine_id' => $med->id,
            'batch_id' => $batch->id,
            'quantity' => 2,
            'unit_price' => 15,
            'subtotal' => 30,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.medicines.delete-all'), [
            'confirmation' => 'DELETE ALL PRODUCTS',
        ]);

        $response->assertRedirect('/medicines');

        // Active medicine query returns 0
        $this->assertEquals(0, Medicine::count());

        // Soft-deleted medicine still exists in database
        $this->assertEquals(1, Medicine::withTrashed()->count());
        $archivedMed = Medicine::withTrashed()->find($med->id);
        $this->assertNotNull($archivedMed);
        $this->assertEquals('archived', $archivedMed->status);
        $this->assertNotNull($archivedMed->deleted_at);

        // Historical invoice and sale item still load the product
        $freshSaleItem = SaleItem::with('medicine')->find($saleItem->id);
        $this->assertNotNull($freshSaleItem);
        $this->assertNotNull($freshSaleItem->medicine);
        $this->assertEquals('Amoxicillin 500mg', $freshSaleItem->medicine->name);

        // Invoices and batches were NOT wiped
        $this->assertEquals(1, Sale::count());
        $this->assertEquals(1, SaleItem::count());
        $this->assertEquals(1, MedicineBatch::count());
    }

    /**
     * Test 8: Products linked to purchases are archived and not cascade-deleted.
     */
    public function test_products_linked_to_purchases_are_archived_and_not_cascade_deleted(): void
    {
        $supplier = Supplier::create([
            'name' => 'Pharma Supplies Ltd',
            'phone' => '03001234567',
        ]);

        $med = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Ciprofloxacin 500mg',
            'dosage_unit' => 'Tablet',
            'base_unit' => 'Tablet',
            'unit_price' => 25,
            'purchase_price' => 18,
        ]);

        $purchaseInvoice = PurchaseInvoice::create([
            'invoice_number' => 'PINV-2001',
            'supplier_id' => $supplier->id,
            'purchase_date' => Carbon::today()->toDateString(),
            'subtotal' => 180,
            'tax_amount' => 0,
            'grand_total' => 180,
        ]);

        $item = PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $purchaseInvoice->id,
            'medicine_id' => $med->id,
            'batch_number' => 'CIP-88',
            'quantity' => 10,
            'purchase_price' => 18,
            'selling_price' => 25,
            'expiry_date' => Carbon::now()->addYear()->toDateString(),
            'total' => 180,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.medicines.delete-all'), [
            'confirmation' => 'DELETE ALL PRODUCTS',
        ]);

        $response->assertRedirect('/medicines');

        // Active medicines count is 0
        $this->assertEquals(0, Medicine::count());

        // Purchase Invoice and Purchase Invoice Item are completely intact
        $this->assertEquals(1, PurchaseInvoice::count());
        $this->assertEquals(1, PurchaseInvoiceItem::count());

        $freshItem = PurchaseInvoiceItem::with('medicine')->find($item->id);
        $this->assertNotNull($freshItem->medicine);
        $this->assertEquals('Ciprofloxacin 500mg', $freshItem->medicine->name);
    }

    /**
     * Test 9: Products with stock movements are archived and movements preserved.
     */
    public function test_products_with_stock_movements_are_archived(): void
    {
        $med = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Paracetamol Drops',
            'dosage_unit' => 'Bottle',
            'base_unit' => 'Bottle',
            'unit_price' => 60,
            'purchase_price' => 45,
        ]);

        StockMovement::create([
            'medicine_id' => $med->id,
            'user_id' => $this->admin->id,
            'type' => 'in',
            'quantity' => 20,
            'previous_stock' => 0,
            'new_stock' => 20,
            'reason' => 'Initial stock intake',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.medicines.delete-all'), [
            'confirmation' => 'DELETE ALL PRODUCTS',
        ]);

        $response->assertRedirect('/medicines');

        // Product is soft-deleted
        $this->assertEquals(0, Medicine::count());
        $this->assertEquals(1, Medicine::withTrashed()->count());

        // Stock movement is preserved
        $this->assertEquals(1, StockMovement::count());
        $movement = StockMovement::with('medicine')->first();
        $this->assertNotNull($movement->medicine);
        $this->assertEquals('Paracetamol Drops', $movement->medicine->name);
    }

    /**
     * Test 10: Product audit log is recorded with admin details and affected IDs.
     */
    public function test_audit_log_is_recorded_with_admin_details(): void
    {
        $med = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Audit Test Item',
            'dosage_unit' => 'Tablet',
            'base_unit' => 'Tablet',
            'unit_price' => 10,
            'purchase_price' => 5,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.medicines.delete-all'), [
            'confirmation' => 'DELETE ALL PRODUCTS',
        ]);

        $response->assertRedirect('/medicines');

        $auditLog = ProductAuditLog::latest()->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals($this->admin->id, $auditLog->user_id);
        $this->assertEquals($this->admin->email, $auditLog->user_email);
        $this->assertEquals('bulk_delete_all_products', $auditLog->action);
        $this->assertContains($med->id, $auditLog->affected_product_ids);
        $this->assertEquals('success', $auditLog->status);
    }

    /**
     * Test 11: Repeated requests are idempotent and handle safely.
     */
    public function test_repeated_requests_handle_safely(): void
    {
        Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Single Item',
            'dosage_unit' => 'Tablet',
            'base_unit' => 'Tablet',
            'unit_price' => 10,
            'purchase_price' => 5,
        ]);

        // First execution
        $res1 = $this->actingAs($this->admin)->post(route('admin.medicines.delete-all'), [
            'confirmation' => 'DELETE ALL PRODUCTS',
        ]);
        $res1->assertRedirect('/medicines');
        $this->assertEquals(0, Medicine::count());

        // Second execution (now empty)
        $res2 = $this->actingAs($this->admin)->post(route('admin.medicines.delete-all'), [
            'confirmation' => 'DELETE ALL PRODUCTS',
        ]);
        $res2->assertRedirect('/medicines');
        $this->assertEquals(0, Medicine::count());
    }

    /**
     * Test 12: Livewire component open and execute delete-all flow.
     */
    public function test_livewire_component_open_and_execute_delete_all_flow(): void
    {
        $med = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Livewire Test Product',
            'dosage_unit' => 'Piece',
            'base_unit' => 'Piece',
            'unit_price' => 100,
            'purchase_price' => 80,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AddMedicine::class)
            ->assertSet('showDeleteAllModal', false)
            ->call('openDeleteAllModal')
            ->assertSet('showDeleteAllModal', true)
            ->assertSet('deleteAllConfirmationInput', '')
            ->assertSee('Bulk Operation: Delete All Products')
            ->assertSee('DELETE ALL PRODUCTS')
            ->set('deleteAllConfirmationInput', 'WRONG TEXT')
            ->call('executeDeleteAll')
            ->assertSee('You must type DELETE ALL PRODUCTS')
            ->set('deleteAllConfirmationInput', 'DELETE ALL PRODUCTS')
            ->call('executeDeleteAll')
            ->assertSet('showDeleteAllModal', false)
            ->assertSee('Bulk operation completed successfully');

        $this->assertEquals(0, Medicine::count());
    }
}
