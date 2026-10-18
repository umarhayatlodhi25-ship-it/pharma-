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
        $defaultEmails = ['admin@pharmacy.com', 'admin@gmail.com'];

        // Find custom / user-added accounts
        $customUsers = DB::table('users')
            ->whereNotIn('email', $defaultEmails)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        if ($customUsers->isNotEmpty()) {
            $primaryUser = $customUsers->first();

            // Ensure at least one remaining user has admin privileges
            $hasAdmin = $customUsers->contains(fn($u) => $u->role === 'admin');
            if (!$hasAdmin) {
                DB::table('users')
                    ->where('id', $primaryUser->id)
                    ->update(['role' => 'admin', 'updated_at' => now()]);
            }

            // Find all default user IDs
            $defaultUserIds = DB::table('users')
                ->whereIn('email', $defaultEmails)
                ->pluck('id')
                ->toArray();

            if (!empty($defaultUserIds)) {
                // Safely reassign foreign keys in all tables referencing users
                $tablesWithUserId = ['sales', 'customer_payments', 'stock_movements', 'hold_invoices'];
                foreach ($tablesWithUserId as $tableName) {
                    if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'user_id')) {
                        DB::table($tableName)
                            ->whereIn('user_id', $defaultUserIds)
                            ->update(['user_id' => $primaryUser->id]);
                    }
                }

                $hospitalTables = ['hospital_bills', 'hospital_bill_payments', 'doctor_settlements', 'doctor_ledgers'];
                foreach ($hospitalTables as $tableName) {
                    if (Schema::hasTable($tableName)) {
                        if (Schema::hasColumn($tableName, 'created_by')) {
                            DB::table($tableName)
                                ->whereIn('created_by', $defaultUserIds)
                                ->update(['created_by' => $primaryUser->id]);
                        }
                        if (Schema::hasColumn($tableName, 'received_by')) {
                            DB::table($tableName)
                                ->whereIn('received_by', $defaultUserIds)
                                ->update(['received_by' => $primaryUser->id]);
                        }
                    }
                }

                // Delete default users permanently
                DB::table('users')
                    ->whereIn('id', $defaultUserIds)
                    ->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to restore default users
    }
};
