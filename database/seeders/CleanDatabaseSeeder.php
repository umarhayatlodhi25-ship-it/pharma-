<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CleanDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key constraints for clean truncation
        Schema::disableForeignKeyConstraints();

        // Truncate data tables
        DB::table('sale_items')->truncate();
        DB::table('sales')->truncate();
        if (Schema::hasTable('hold_invoices')) {
            DB::table('hold_invoices')->truncate();
        }
        if (Schema::hasTable('purchase_invoice_items')) {
            DB::table('purchase_invoice_items')->truncate();
        }
        if (Schema::hasTable('purchase_invoices')) {
            DB::table('purchase_invoices')->truncate();
        }
        if (Schema::hasTable('purchases')) {
            DB::table('purchases')->truncate();
        }
        DB::table('medicine_batches')->truncate();
        DB::table('medicines')->truncate();
        DB::table('categories')->truncate();
        if (Schema::hasTable('suppliers')) {
            DB::table('suppliers')->truncate();
        }
        if (Schema::hasTable('customers')) {
            DB::table('customers')->truncate();
        }

        Schema::enableForeignKeyConstraints();

        // Ensure Admin Account exists if no other users exist
        if (User::withTrashed()->count() === 0) {
            User::create([
                'name' => 'Admin User',
                'email' => 'admin@pharmacy.com',
                'password' => 'admin123',
                'role' => 'admin',
            ]);
        }
    }
}
