<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExpiryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiry_report_page_loads_and_filters_correctly()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $category = Category::create([
            'name' => 'Pharmaceuticals',
            'slug' => 'pharmaceuticals',
            'product_type' => 'medicine',
        ]);

        // 1. Medicine with expiry tracking enabled
        $medWithExpiry = Medicine::create([
            'category_id' => $category->id,
            'product_type' => 'medicine',
            'name' => 'Panadol Extra',
            'brand' => 'GSK',
            'base_unit' => 'Tablet',
            'has_expiry' => true,
            'track_batches' => true,
            'purchase_price' => 50,
            'unit_price' => 60,
            'base_unit_selling_price' => 60,
            'alert_quantity' => 10,
        ]);

        // 2. General store item without expiry tracking
        $medNoExpiry = Medicine::create([
            'category_id' => $category->id,
            'product_type' => 'general',
            'name' => 'Cotton Bandage',
            'brand' => 'FirstAid',
            'base_unit' => 'Piece',
            'has_expiry' => false,
            'track_batches' => false,
            'purchase_price' => 20,
            'unit_price' => 30,
            'base_unit_selling_price' => 30,
            'alert_quantity' => 5,
        ]);

        $today = Carbon::today();

        // Expired batch (should appear in expired and all)
        MedicineBatch::create([
            'medicine_id' => $medWithExpiry->id,
            'batch_number' => 'BATCH-EXPIRED',
            'expiry_date' => $today->copy()->subDays(10)->toDateString(),
            'quantity' => 10,
            'purchase_price' => 50.00,
            'selling_price' => 60.00,
        ]);

        // Expiring soon batch within 30 days (should appear in 30days and all)
        MedicineBatch::create([
            'medicine_id' => $medWithExpiry->id,
            'batch_number' => 'BATCH-SOON-20D',
            'expiry_date' => $today->copy()->addDays(20)->toDateString(),
            'quantity' => 20,
            'purchase_price' => 60.00,
            'selling_price' => 70.00,
        ]);

        // Non-expiry medicine batch (should NEVER appear in expiry report)
        MedicineBatch::create([
            'medicine_id' => $medNoExpiry->id,
            'batch_number' => 'BATCH-NO-EXPIRY',
            'expiry_date' => $today->copy()->subDays(5)->toDateString(),
            'quantity' => 15,
            'purchase_price' => 30.00,
            'selling_price' => 35.00,
        ]);

        // Test main report page (default: all <= 90 days)
        $response = $this->actingAs($admin)->get('/reports/expiry');
        $response->assertStatus(200);
        $response->assertSee('BATCH-EXPIRED');
        $response->assertSee('BATCH-SOON-20D');
        $response->assertDontSee('BATCH-NO-EXPIRY');

        // Test status=expired filter
        $responseExpired = $this->actingAs($admin)->get('/reports/expiry?status=expired');
        $responseExpired->assertStatus(200);
        $responseExpired->assertSee('BATCH-EXPIRED');
        $responseExpired->assertDontSee('BATCH-SOON-20D');
        $responseExpired->assertDontSee('BATCH-NO-EXPIRY');

        // Test status=30days filter
        $response30Days = $this->actingAs($admin)->get('/reports/expiry?status=30days');
        $response30Days->assertStatus(200);
        $response30Days->assertDontSee('BATCH-EXPIRED');
        $response30Days->assertSee('BATCH-SOON-20D');
        $response30Days->assertDontSee('BATCH-NO-EXPIRY');

        // Test reports overview page
        $overviewResponse = $this->actingAs($admin)->get('/reports');
        $overviewResponse->assertStatus(200);
    }
}
