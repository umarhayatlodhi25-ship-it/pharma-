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
        // 1. Add billing_type to hospital_bills
        Schema::table('hospital_bills', function (Blueprint $table) {
            if (!Schema::hasColumn('hospital_bills', 'billing_type')) {
                $table->string('billing_type', 30)->default('direct')->after('bill_number');
                $table->index('billing_type');
            }
        });

        // 2. Hospital Bill Items (supports multiple services per bill)
        if (!Schema::hasTable('hospital_bill_items')) {
            Schema::create('hospital_bill_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hospital_bill_id')->constrained('hospital_bills')->cascadeOnDelete();
                $table->foreignId('hospital_service_id')->nullable()->constrained('hospital_services')->nullOnDelete();
                $table->string('service_name', 255);
                $table->string('service_code', 50)->nullable();
                $table->decimal('quantity', 8, 2)->default(1.00);
                $table->decimal('unit_price', 10, 2)->default(0.00);
                $table->decimal('subtotal', 10, 2)->default(0.00);
                $table->decimal('discount_amount', 10, 2)->default(0.00);
                $table->decimal('total_price', 10, 2)->default(0.00);
                $table->string('notes', 255)->nullable();
                $table->timestamps();

                $table->index('hospital_bill_id');
                $table->index('hospital_service_id');
            });
        }

        // 3. Hospital Account Ledgers (double-entry audit trail for hospital financial collections & receivables)
        if (!Schema::hasTable('hospital_account_ledgers')) {
            Schema::create('hospital_account_ledgers', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_number', 50)->unique();
                $table->date('entry_date');
                $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
                $table->foreignId('hospital_bill_id')->nullable()->constrained('hospital_bills')->nullOnDelete();
                $table->foreignId('hospital_bill_payment_id')->nullable()->constrained('hospital_bill_payments')->nullOnDelete();
                $table->foreignId('patient_token_id')->nullable()->constrained('patient_tokens')->nullOnDelete();
                $table->string('transaction_type', 30); // 'receivable', 'collection', 'refund', 'adjustment'
                $table->string('description', 255);
                $table->decimal('gross_amount', 10, 2)->default(0.00);
                $table->decimal('discount_amount', 10, 2)->default(0.00);
                $table->decimal('amount_received', 10, 2)->default(0.00);
                $table->decimal('outstanding_balance', 10, 2)->default(0.00);
                $table->string('payment_method', 50)->nullable()->default('cash');
                $table->string('status', 30)->default('posted'); // 'posted', 'cancelled', 'reversed'
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('entry_date');
                $table->index('patient_id');
                $table->index('transaction_type');
                $table->index('status');
                $table->index('hospital_bill_id');
            });
        }

        // 4. Payment Allocations (tracks exact allocation of payments across current bill and previous balances)
        if (!Schema::hasTable('hospital_bill_payment_allocations')) {
            Schema::create('hospital_bill_payment_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hospital_bill_payment_id')->constrained('hospital_bill_payments')->cascadeOnDelete();
                $table->foreignId('hospital_bill_id')->constrained('hospital_bills')->cascadeOnDelete();
                $table->decimal('allocated_amount', 10, 2)->default(0.00);
                $table->string('allocation_type', 30)->default('current_bill'); // 'current_bill', 'previous_balance'
                $table->timestamps();

                $table->index('hospital_bill_payment_id');
                $table->index('hospital_bill_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospital_bill_payment_allocations');
        Schema::dropIfExists('hospital_account_ledgers');
        Schema::dropIfExists('hospital_bill_items');

        Schema::table('hospital_bills', function (Blueprint $table) {
            if (Schema::hasColumn('hospital_bills', 'billing_type')) {
                $table->dropIndex(['billing_type']);
                $table->dropColumn('billing_type');
            }
        });
    }
};
