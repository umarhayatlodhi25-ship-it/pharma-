<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patient_tokens', function (Blueprint $table) {
            $table->foreignId('hospital_service_id')->nullable()->after('doctor_id')->constrained('hospital_services')->nullOnDelete();
            $table->foreignId('hospital_bill_id')->nullable()->after('hospital_service_id')->constrained('hospital_bills')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_tokens', function (Blueprint $table) {
            $table->dropForeign(['hospital_service_id']);
            $table->dropForeign(['hospital_bill_id']);
            $table->dropColumn(['hospital_service_id', 'hospital_bill_id']);
        });
    }
};
