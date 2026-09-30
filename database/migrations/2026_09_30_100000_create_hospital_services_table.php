<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hospital_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('service_type', 50)->default('consultation'); // consultation, procedure, diagnostic, nursing, other
            $table->decimal('default_fee', 10, 2)->default(0.00);
            $table->decimal('doctor_share_percentage', 5, 2)->default(0.00);
            $table->decimal('hospital_share_percentage', 5, 2)->default(100.00);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('service_type');
            $table->index('is_active');
        });

        // Seed initial default hospital services
        $now = now();
        $defaults = [
            [
                'name' => 'Doctor Consultation',
                'code' => 'SRV-CONSULT',
                'service_type' => 'consultation',
                'default_fee' => 500.00,
                'doctor_share_percentage' => 70.00,
                'hospital_share_percentage' => 30.00,
                'is_active' => true,
                'description' => 'General / Specialist OPD Doctor Consultation',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Sugar Check',
                'code' => 'SRV-SUGAR',
                'service_type' => 'diagnostic',
                'default_fee' => 100.00,
                'doctor_share_percentage' => 0.00,
                'hospital_share_percentage' => 100.00,
                'is_active' => true,
                'description' => 'Random Blood Sugar (RBS) Fasting / Glucometer Test',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Blood Pressure Check',
                'code' => 'SRV-BP',
                'service_type' => 'diagnostic',
                'default_fee' => 50.00,
                'doctor_share_percentage' => 0.00,
                'hospital_share_percentage' => 100.00,
                'is_active' => true,
                'description' => 'Vital Signs BP Measurement',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Drip',
                'code' => 'SRV-DRIP',
                'service_type' => 'nursing',
                'default_fee' => 500.00,
                'doctor_share_percentage' => 30.00,
                'hospital_share_percentage' => 70.00,
                'is_active' => true,
                'description' => 'Intravenous (IV) Infusion Administration',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Injection',
                'code' => 'SRV-INJ',
                'service_type' => 'procedure',
                'default_fee' => 100.00,
                'doctor_share_percentage' => 0.00,
                'hospital_share_percentage' => 100.00,
                'is_active' => true,
                'description' => 'IM / IV / SC Injection Administration',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Dressing',
                'code' => 'SRV-DRESS',
                'service_type' => 'procedure',
                'default_fee' => 200.00,
                'doctor_share_percentage' => 20.00,
                'hospital_share_percentage' => 80.00,
                'is_active' => true,
                'description' => 'Wound Cleaning & Bandaging',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Nebulization',
                'code' => 'SRV-NEB',
                'service_type' => 'procedure',
                'default_fee' => 250.00,
                'doctor_share_percentage' => 0.00,
                'hospital_share_percentage' => 100.00,
                'is_active' => true,
                'description' => 'Respiratory Nebulizer Therapy',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Other Hospital Service',
                'code' => 'SRV-OTHER',
                'service_type' => 'other',
                'default_fee' => 0.00,
                'doctor_share_percentage' => 50.00,
                'hospital_share_percentage' => 50.00,
                'is_active' => true,
                'description' => 'General / Miscellaneous Hospital Procedure',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('hospital_services')->insert($defaults);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospital_services');
    }
};
