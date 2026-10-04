<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Medicine;
use Illuminate\Support\Facades\DB;

$medicine = Medicine::first();
if (!$medicine) {
    echo "No medicine found\n";
    exit;
}

$id = $medicine->id;
echo "Trying to delete medicine ID: $id\n";

try {
    DB::transaction(function () use ($medicine, $id) {
        DB::table('sale_items')->where('medicine_id', $id)->delete();
        DB::table('sales_return_items')->where('medicine_id', $id)->delete();
        DB::table('purchase_return_items')->where('medicine_id', $id)->delete();
        DB::table('purchase_invoice_items')->where('medicine_id', $id)->delete();
        if (\Illuminate\Support\Facades\Schema::hasTable('hold_invoices')) {
            DB::table('hold_invoices')->where('medicine_id', $id)->delete();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('purchases')) {
            DB::table('purchases')->where('medicine_id', $id)->delete();
        }

        \App\Models\StockMovement::where('medicine_id', $id)->delete();
        \App\Models\MedicineBatch::where('medicine_id', $id)->delete();
        \App\Models\MedicinePackaging::where('medicine_id', $id)->delete();
        \App\Models\Inventory::where('medicine_id', $id)->delete();

        $medicine->delete();
        echo "Deleted successfully!\n";
    });
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
