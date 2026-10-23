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
            $table->decimal('previous_due', 10, 2)->default(0.00)->after('notes');
            $table->decimal('paid_today', 10, 2)->default(0.00)->after('previous_due');
            $table->decimal('balance_due', 10, 2)->default(0.00)->after('paid_today');
        });

        Schema::table('hospital_bills', function (Blueprint $table) {
            $table->decimal('previous_due', 10, 2)->default(0.00)->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_tokens', function (Blueprint $table) {
            $table->dropColumn(['previous_due', 'paid_today', 'balance_due']);
        });

        Schema::table('hospital_bills', function (Blueprint $table) {
            $table->dropColumn(['previous_due']);
        });
    }
};
