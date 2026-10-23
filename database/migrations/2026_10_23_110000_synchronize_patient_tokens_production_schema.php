<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Disable transaction block on PostgreSQL/Neon so that pooled connections
     * and DDL commands execute safely without transaction abort errors.
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('patient_tokens')) {
            Schema::table('patient_tokens', function (Blueprint $table) {
                // Doctor & Service References
                if (!Schema::hasColumn('patient_tokens', 'doctor_id')) {
                    $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
                }
                if (!Schema::hasColumn('patient_tokens', 'hospital_service_id')) {
                    $table->foreignId('hospital_service_id')->nullable()->constrained('hospital_services')->nullOnDelete();
                }
                if (!Schema::hasColumn('patient_tokens', 'hospital_bill_id')) {
                    $table->foreignId('hospital_bill_id')->nullable()->constrained('hospital_bills')->nullOnDelete();
                }

                // Payment & Consultation Fields
                if (!Schema::hasColumn('patient_tokens', 'payment_type')) {
                    $table->string('payment_type', 20)->default('paid');
                }
                if (!Schema::hasColumn('patient_tokens', 'consultation_fee')) {
                    $table->decimal('consultation_fee', 10, 2)->default(0.00);
                }
                if (!Schema::hasColumn('patient_tokens', 'charged_amount')) {
                    $table->decimal('charged_amount', 10, 2)->default(0.00);
                }
                if (!Schema::hasColumn('patient_tokens', 'free_reason')) {
                    $table->string('free_reason', 100)->nullable();
                }
                if (!Schema::hasColumn('patient_tokens', 'other_reason')) {
                    $table->string('other_reason', 255)->nullable();
                }

                // Doctor & Hospital Shares
                if (!Schema::hasColumn('patient_tokens', 'doctor_share_percentage')) {
                    $table->decimal('doctor_share_percentage', 5, 2)->default(70.00);
                }
                if (!Schema::hasColumn('patient_tokens', 'hospital_share_percentage')) {
                    $table->decimal('hospital_share_percentage', 5, 2)->default(30.00);
                }
                if (!Schema::hasColumn('patient_tokens', 'doctor_share_amount')) {
                    $table->decimal('doctor_share_amount', 10, 2)->default(0.00);
                }
                if (!Schema::hasColumn('patient_tokens', 'hospital_share_amount')) {
                    $table->decimal('hospital_share_amount', 10, 2)->default(0.00);
                }
                if (!Schema::hasColumn('patient_tokens', 'discount_amount')) {
                    $table->decimal('discount_amount', 10, 2)->default(0.00);
                }

                // Pending Balance & Carry-Forward Fields (Core Missing Fields)
                if (!Schema::hasColumn('patient_tokens', 'previous_due')) {
                    $table->decimal('previous_due', 10, 2)->default(0.00);
                }
                if (!Schema::hasColumn('patient_tokens', 'paid_today')) {
                    $table->decimal('paid_today', 10, 2)->default(0.00);
                }
                if (!Schema::hasColumn('patient_tokens', 'balance_due')) {
                    $table->decimal('balance_due', 10, 2)->default(0.00);
                }
            });

            // PostgreSQL Status Check Constraint update (supports 'in_consultation')
            try {
                if (DB::getDriverName() === 'pgsql') {
                    DB::statement("ALTER TABLE patient_tokens DROP CONSTRAINT IF EXISTS patient_tokens_status_check");
                    DB::statement("ALTER TABLE patient_tokens ADD CONSTRAINT patient_tokens_status_check CHECK (status IN ('waiting', 'called', 'in_consultation', 'completed', 'cancelled'))");
                }
            } catch (\Throwable $e) {
                // Ignore if constraint doesn't exist or is not an enum in this driver
            }
        }

        // Essential dependent fields on hospital_bills created atomically with tokens
        if (Schema::hasTable('hospital_bills')) {
            Schema::table('hospital_bills', function (Blueprint $table) {
                if (!Schema::hasColumn('hospital_bills', 'previous_due')) {
                    $table->decimal('previous_due', 10, 2)->default(0.00);
                }
                if (!Schema::hasColumn('hospital_bills', 'billing_type')) {
                    $table->string('billing_type', 30)->default('opd');
                }
                if (!Schema::hasColumn('hospital_bills', 'patient_token_id')) {
                    $table->foreignId('patient_token_id')->nullable()->constrained('patient_tokens')->nullOnDelete();
                }
                if (!Schema::hasColumn('hospital_bills', 'hospital_service_id')) {
                    $table->foreignId('hospital_service_id')->nullable()->constrained('hospital_services')->nullOnDelete();
                }
                if (!Schema::hasColumn('hospital_bills', 'doctor_share')) {
                    $table->decimal('doctor_share', 10, 2)->default(0.00);
                }
                if (!Schema::hasColumn('hospital_bills', 'hospital_share')) {
                    $table->decimal('hospital_share', 10, 2)->default(0.00);
                }
                if (!Schema::hasColumn('hospital_bills', 'doctor_share_percentage')) {
                    $table->decimal('doctor_share_percentage', 5, 2)->default(70.00);
                }
                if (!Schema::hasColumn('hospital_bills', 'hospital_share_percentage')) {
                    $table->decimal('hospital_share_percentage', 5, 2)->default(30.00);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe forward-only compatibility migration preserves all added columns in production
    }
};
