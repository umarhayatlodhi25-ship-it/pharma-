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
        // 1. Add status column to users table if it does not already exist
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'status')) {
                    $table->string('status', 30)->default('active')->after('role');
                    $table->index('status');
                }
            });
        }

        // 2. Create doctor_registration_requests table
        if (!Schema::hasTable('doctor_registration_requests')) {
            Schema::create('doctor_registration_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('doctor_identifier', 100)->nullable();
                $table->string('name', 255);
                $table->string('email', 255);
                $table->string('phone', 50);
                $table->string('license_number', 100)->nullable();
                $table->string('identity_document_type', 50)->nullable()->default('pmdc_license');
                $table->boolean('is_verified')->default(false);
                $table->string('verification_notes', 500)->nullable();
                $table->string('status', 30)->default('pending'); // pending, approved, rejected, suspended
                $table->text('admin_remarks')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('terms_accepted_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index('status');
                $table->index('doctor_id');
                $table->index('user_id');
                $table->index('email');
                $table->index('phone');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_registration_requests');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'status')) {
                    $table->dropIndex(['status']);
                    $table->dropColumn('status');
                }
            });
        }
    }
};
