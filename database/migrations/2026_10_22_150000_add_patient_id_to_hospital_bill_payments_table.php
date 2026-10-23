<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('hospital_bill_payments')) {
            Schema::table('hospital_bill_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('hospital_bill_payments', 'patient_id')) {
                    $table->foreignId('patient_id')->nullable()->after('hospital_bill_id')->constrained('patients')->nullOnDelete();
                    $table->index('patient_id');
                }
            });
        }

        // Backfill existing payments from hospital_bills
        try {
            DB::statement("UPDATE hospital_bill_payments SET patient_id = (SELECT patient_id FROM hospital_bills WHERE hospital_bills.id = hospital_bill_payments.hospital_bill_id)");
        } catch (\Exception $e) {
            // Ignore if table was empty
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hospital_bill_payments', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropIndex(['patient_id']);
            $table->dropColumn('patient_id');
        });
    }
};
