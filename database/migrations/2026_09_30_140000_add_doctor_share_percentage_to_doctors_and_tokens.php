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
        // 1. Add Doctor Share % and Hospital Share % to doctors table
        Schema::table('doctors', function (Blueprint $table) {
            $table->decimal('doctor_share_percentage', 5, 2)->default(70.00)->after('consultation_fee');
            $table->decimal('hospital_share_percentage', 5, 2)->default(30.00)->after('doctor_share_percentage');
        });

        // 2. Add transaction snapshot fields to patient_tokens table
        Schema::table('patient_tokens', function (Blueprint $table) {
            $table->decimal('doctor_share_percentage', 5, 2)->default(70.00)->after('charged_amount');
            $table->decimal('hospital_share_percentage', 5, 2)->default(30.00)->after('doctor_share_percentage');
            $table->decimal('doctor_share_amount', 10, 2)->default(0.00)->after('hospital_share_percentage');
            $table->decimal('hospital_share_amount', 10, 2)->default(0.00)->after('doctor_share_amount');
            $table->decimal('discount_amount', 10, 2)->default(0.00)->after('hospital_share_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_tokens', function (Blueprint $table) {
            $table->dropColumn([
                'doctor_share_percentage',
                'hospital_share_percentage',
                'doctor_share_amount',
                'hospital_share_amount',
                'discount_amount',
            ]);
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn([
                'doctor_share_percentage',
                'hospital_share_percentage',
            ]);
        });
    }
};
