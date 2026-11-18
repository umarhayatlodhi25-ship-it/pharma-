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
        // 1. Account Profiles Table Updates
        if (Schema::hasTable('account_profiles')) {
            Schema::table('account_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('account_profiles', 'queue_scope')) {
                    $table->string('queue_scope')->default('doctor_wise')->after('show_token_status');
                }
                if (!Schema::hasColumn('account_profiles', 'voice_announcement_enabled')) {
                    $table->boolean('voice_announcement_enabled')->default(true)->after('queue_scope');
                }
                if (!Schema::hasColumn('account_profiles', 'voice_announcement_language')) {
                    $table->string('voice_announcement_language')->default('both')->after('voice_announcement_enabled');
                }
                if (!Schema::hasColumn('account_profiles', 'voice_repeat_count')) {
                    $table->integer('voice_repeat_count')->default(1)->after('voice_announcement_language');
                }
                if (!Schema::hasColumn('account_profiles', 'voice_volume')) {
                    $table->integer('voice_volume')->default(100)->after('voice_repeat_count');
                }
                if (!Schema::hasColumn('account_profiles', 'lcd_refresh_interval_sec')) {
                    $table->integer('lcd_refresh_interval_sec')->default(3)->after('voice_volume');
                }
                if (!Schema::hasColumn('account_profiles', 'lcd_message_ticker')) {
                    $table->string('lcd_message_ticker', 500)->nullable()->after('lcd_refresh_interval_sec');
                }
            });
        }

        // 2. Patient Tokens Table Updates
        if (Schema::hasTable('patient_tokens')) {
            Schema::table('patient_tokens', function (Blueprint $table) {
                if (!Schema::hasColumn('patient_tokens', 'uuid')) {
                    $table->string('uuid')->nullable()->after('id');
                }
                if (!Schema::hasColumn('patient_tokens', 'department_id')) {
                    $table->unsignedBigInteger('department_id')->nullable()->after('doctor_id');
                }
                if (!Schema::hasColumn('patient_tokens', 'room_number')) {
                    $table->string('room_number')->nullable()->after('department_id');
                }
                if (!Schema::hasColumn('patient_tokens', 'token_prefix')) {
                    $table->string('token_prefix')->nullable()->after('token_number');
                }
                if (!Schema::hasColumn('patient_tokens', 'queue_sequence')) {
                    $table->integer('queue_sequence')->nullable()->after('token_prefix');
                }
                if (!Schema::hasColumn('patient_tokens', 'call_count')) {
                    $table->integer('call_count')->default(0)->after('status');
                }
                if (!Schema::hasColumn('patient_tokens', 'started_at')) {
                    $table->dateTime('started_at')->nullable()->after('called_at');
                }
                if (!Schema::hasColumn('patient_tokens', 'held_at')) {
                    $table->dateTime('held_at')->nullable()->after('started_at');
                }
                if (!Schema::hasColumn('patient_tokens', 'skipped_at')) {
                    $table->dateTime('skipped_at')->nullable()->after('held_at');
                }
                if (!Schema::hasColumn('patient_tokens', 'cancelled_at')) {
                    $table->dateTime('cancelled_at')->nullable()->after('completed_at');
                }
                if (!Schema::hasColumn('patient_tokens', 'cancellation_reason')) {
                    $table->string('cancellation_reason')->nullable()->after('cancelled_at');
                }
                if (!Schema::hasColumn('patient_tokens', 'generated_by')) {
                    $table->unsignedBigInteger('generated_by')->nullable()->after('cancellation_reason');
                }
            });
        }

        // 3. Token Counters Table
        if (!Schema::hasTable('token_counters')) {
            Schema::create('token_counters', function (Blueprint $table) {
                $table->id();
                $table->string('queue_key')->index();
                $table->date('token_date')->index();
                $table->integer('current_number')->default(0);
                $table->timestamps();
            });
        }

        // 4. Token Call Logs Table
        if (!Schema::hasTable('token_call_logs')) {
            Schema::create('token_call_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('patient_token_id')->index();
                $table->string('action'); // 'call', 'recall', 'start_consultation', 'complete', 'skip', 'hold', 'resume', 'cancel'
                $table->string('from_status')->nullable();
                $table->string('to_status');
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->string('room_number')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('notes')->nullable();
                $table->dateTime('called_at');
                $table->timestamps();
            });
        }

        // 5. Sync Outbox Table
        if (!Schema::hasTable('sync_outbox')) {
            Schema::create('sync_outbox', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->string('event_type');
                $table->string('aggregate_type');
                $table->string('aggregate_id');
                $table->text('payload');
                $table->string('status')->default('pending')->index();
                $table->integer('attempts')->default(0);
                $table->text('last_error')->nullable();
                $table->dateTime('synced_at')->nullable();
                $table->timestamps();
            });
        }

        // 6. Sync Logs Table
        if (!Schema::hasTable('sync_logs')) {
            Schema::create('sync_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sync_outbox_id')->index();
                $table->string('event_type');
                $table->string('status');
                $table->integer('response_code')->nullable();
                $table->text('response_body')->nullable();
                $table->integer('execution_time_ms')->nullable();
                $table->dateTime('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('sync_outbox');
        Schema::dropIfExists('token_call_logs');
        Schema::dropIfExists('token_counters');
    }
};

