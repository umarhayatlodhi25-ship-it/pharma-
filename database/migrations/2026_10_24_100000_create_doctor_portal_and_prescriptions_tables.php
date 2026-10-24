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
        // 1. Add doctor portal linking and schedule fields to doctors table
        if (Schema::hasTable('doctors')) {
            Schema::table('doctors', function (Blueprint $table) {
                if (!Schema::hasColumn('doctors', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('doctors', 'room_number')) {
                    $table->string('room_number', 50)->nullable();
                }
                if (!Schema::hasColumn('doctors', 'start_time')) {
                    $table->string('start_time', 20)->nullable()->default('09:00');
                }
                if (!Schema::hasColumn('doctors', 'end_time')) {
                    $table->string('end_time', 20)->nullable()->default('17:00');
                }
                if (!Schema::hasColumn('doctors', 'schedule_days')) {
                    $table->text('schedule_days')->nullable();
                }
            });
        }

        // 2. Add clinical examination & vitals fields to patient_tokens table
        if (Schema::hasTable('patient_tokens')) {
            Schema::table('patient_tokens', function (Blueprint $table) {
                if (!Schema::hasColumn('patient_tokens', 'symptoms')) {
                    $table->text('symptoms')->nullable();
                }
                if (!Schema::hasColumn('patient_tokens', 'diagnosis')) {
                    $table->text('diagnosis')->nullable();
                }
                if (!Schema::hasColumn('patient_tokens', 'clinical_notes')) {
                    $table->text('clinical_notes')->nullable();
                }
                if (!Schema::hasColumn('patient_tokens', 'follow_up_date')) {
                    $table->date('follow_up_date')->nullable();
                }
                if (!Schema::hasColumn('patient_tokens', 'follow_up_instructions')) {
                    $table->text('follow_up_instructions')->nullable();
                }
                if (!Schema::hasColumn('patient_tokens', 'blood_pressure')) {
                    $table->string('blood_pressure', 30)->nullable();
                }
                if (!Schema::hasColumn('patient_tokens', 'pulse_rate')) {
                    $table->string('pulse_rate', 30)->nullable();
                }
                if (!Schema::hasColumn('patient_tokens', 'temperature')) {
                    $table->string('temperature', 30)->nullable();
                }
                if (!Schema::hasColumn('patient_tokens', 'weight')) {
                    $table->string('weight', 30)->nullable();
                }
            });
        }

        // 3. Create prescriptions table
        if (!Schema::hasTable('prescriptions')) {
            Schema::create('prescriptions', function (Blueprint $table) {
                $table->id();
                $table->string('prescription_number', 50)->unique();
                $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
                $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
                $table->foreignId('patient_token_id')->nullable()->constrained('patient_tokens')->nullOnDelete();
                $table->text('diagnosis')->nullable();
                $table->text('clinical_notes')->nullable();
                $table->date('follow_up_date')->nullable();
                $table->text('follow_up_instructions')->nullable();
                $table->string('status', 20)->default('active'); // active, dispensed, cancelled
                $table->boolean('is_finalized')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('patient_id');
                $table->index('doctor_id');
                $table->index('patient_token_id');
                $table->index('status');
                $table->index('created_at');
            });
        }

        // 4. Create prescription_items table
        if (!Schema::hasTable('prescription_items')) {
            Schema::create('prescription_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
                $table->foreignId('medicine_id')->nullable()->constrained('medicines')->nullOnDelete();
                $table->string('medicine_name', 255);
                $table->string('dosage', 100)->nullable();       // e.g. 500mg, 1 tablet
                $table->string('frequency', 100)->nullable();    // e.g. 1-0-1, TDS
                $table->string('duration', 100)->nullable();     // e.g. 5 days, 1 week
                $table->string('instructions', 255)->nullable(); // e.g. After meals
                $table->unsignedInteger('quantity')->default(1);
                $table->timestamps();

                $table->index('prescription_id');
                $table->index('medicine_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');

        if (Schema::hasTable('patient_tokens')) {
            Schema::table('patient_tokens', function (Blueprint $table) {
                $table->dropColumn([
                    'symptoms',
                    'diagnosis',
                    'clinical_notes',
                    'follow_up_date',
                    'follow_up_instructions',
                    'blood_pressure',
                    'pulse_rate',
                    'temperature',
                    'weight',
                ]);
            });
        }

        if (Schema::hasTable('doctors')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn([
                    'user_id',
                    'room_number',
                    'start_time',
                    'end_time',
                    'schedule_days',
                ]);
            });
        }
    }
};
