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
        // 1. Hospital Bills / Invoices
        Schema::create('hospital_bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number', 50)->unique();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('hospital_service_id')->nullable()->constrained('hospital_services')->nullOnDelete();
            $table->foreignId('patient_token_id')->nullable()->constrained('patient_tokens')->nullOnDelete();
            $table->date('bill_date');
            
            // Financial amounts
            $table->decimal('total_amount', 10, 2)->default(0.00);      // Gross fee
            $table->decimal('discount_amount', 10, 2)->default(0.00);   // Free or discounted amount
            $table->decimal('net_amount', 10, 2)->default(0.00);        // total_amount - discount_amount
            $table->decimal('paid_amount', 10, 2)->default(0.00);       // Collected amount so far
            $table->decimal('due_amount', 10, 2)->default(0.00);        // Remaining unpaid balance

            // Revenue split percentages and recognized amounts (based on actual collected payments)
            $table->decimal('doctor_share_percentage', 5, 2)->default(0.00);
            $table->decimal('hospital_share_percentage', 5, 2)->default(100.00);
            $table->decimal('doctor_share', 10, 2)->default(0.00);      // Recognized doctor payable
            $table->decimal('hospital_share', 10, 2)->default(0.00);    // Recognized hospital revenue

            // Payment and status metadata
            $table->string('payment_method', 50)->default('cash');      // cash, card, bank_transfer, other
            $table->string('payment_status', 30)->default('paid');      // paid, free, partial, pending
            $table->string('free_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('bill_date');
            $table->index('payment_status');
            $table->index('payment_method');
            $table->index('doctor_id');
            $table->index('patient_id');
        });

        // 2. Hospital Bill Payments (tracks each receipt / installment against a bill)
        Schema::create('hospital_bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_bill_id')->constrained('hospital_bills')->cascadeOnDelete();
            $table->string('payment_number', 50)->unique();
            $table->date('payment_date');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->decimal('doctor_share', 10, 2)->default(0.00);
            $table->decimal('hospital_share', 10, 2)->default(0.00);
            $table->string('payment_method', 50)->default('cash');
            $table->string('reference_note', 255)->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('payment_date');
            $table->index('payment_method');
        });

        // 3. Doctor Settlements (payouts from hospital to doctor)
        Schema::create('doctor_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_number', 50)->unique();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->date('settlement_date');
            $table->decimal('previous_payable', 10, 2)->default(0.00);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('remaining_payable', 10, 2)->default(0.00);
            $table->string('payment_method', 50)->default('cash');      // cash, bank_transfer, cheque, other
            $table->string('reference_note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('doctor_id');
            $table->index('settlement_date');
        });

        // 4. Doctor Ledgers (double-entry audit trail for doctor payables & payments)
        Schema::create('doctor_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('hospital_bill_id')->nullable()->constrained('hospital_bills')->nullOnDelete();
            $table->foreignId('hospital_bill_payment_id')->nullable()->constrained('hospital_bill_payments')->nullOnDelete();
            $table->foreignId('doctor_settlement_id')->nullable()->constrained('doctor_settlements')->nullOnDelete();
            $table->date('entry_date');
            $table->enum('transaction_type', ['credit', 'debit']); // credit: doctor earned share, debit: paid to doctor
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->decimal('balance_after', 10, 2)->default(0.00);
            $table->string('description', 255);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('doctor_id');
            $table->index('entry_date');
            $table->index('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_ledgers');
        Schema::dropIfExists('doctor_settlements');
        Schema::dropIfExists('hospital_bill_payments');
        Schema::dropIfExists('hospital_bills');
    }
};
