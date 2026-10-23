<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('patient_tokens')) {
            Schema::table('patient_tokens', function (Blueprint $table) {
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
        }

        if (Schema::hasTable('hospital_bills')) {
            Schema::table('hospital_bills', function (Blueprint $table) {
                if (!Schema::hasColumn('hospital_bills', 'previous_due')) {
                    $table->decimal('previous_due', 10, 2)->default(0.00);
                }
            });
        }
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
