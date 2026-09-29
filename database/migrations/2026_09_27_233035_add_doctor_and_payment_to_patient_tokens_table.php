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
            $table->foreignId('doctor_id')->nullable()->after('patient_id')->constrained('doctors')->nullOnDelete();
            $table->string('payment_type', 20)->default('paid')->after('token_date');
            $table->decimal('consultation_fee', 10, 2)->default(0.00)->after('payment_type');
            $table->decimal('charged_amount', 10, 2)->default(0.00)->after('consultation_fee');
            $table->string('free_reason', 100)->nullable()->after('charged_amount');
            $table->string('other_reason', 255)->nullable()->after('free_reason');

            $table->index('doctor_id');
            $table->index('payment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_tokens', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropColumn([
                'doctor_id',
                'payment_type',
                'consultation_fee',
                'charged_amount',
                'free_reason',
                'other_reason'
            ]);
        });
    }
};
