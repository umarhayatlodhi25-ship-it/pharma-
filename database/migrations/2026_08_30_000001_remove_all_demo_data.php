<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        $tablesToClean = [
            'stock_movements',
            'inventories',
            'sale_items',
            'sales',
            'hold_invoices',
            'sales_return_items',
            'sales_returns',
            'purchase_return_items',
            'purchase_returns',
            'purchase_invoice_items',
            'purchase_invoices',
            'purchases',
            'medicine_packagings',
            'medicine_batches',
            'medicines',
        ];

        foreach ($tablesToClean as $table) {
            if (Schema::hasTable($table)) {
                try {
                    DB::table($table)->delete();
                } catch (\Exception $e) {
                    // Ignore errors during deletion
                }
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed
    }
};
