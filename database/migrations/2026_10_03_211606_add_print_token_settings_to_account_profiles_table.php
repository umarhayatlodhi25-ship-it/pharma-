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
        Schema::table('account_profiles', function (Blueprint $table) {
            $table->string('token_prefix', 10)->nullable();
            $table->integer('token_start_number')->default(1);
            $table->boolean('daily_token_reset')->default(false);
            $table->boolean('auto_print_token')->default(false);
            $table->string('default_token_status')->default('Waiting');
            $table->integer('thermal_paper_size')->default(80);
            $table->integer('print_copies')->default(1);
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_organization_name')->default(true);
            $table->boolean('show_patient_id')->default(true);
            $table->boolean('show_doctor_name')->default(true);
            $table->boolean('show_date')->default(true);
            $table->boolean('show_time')->default(true);
            $table->boolean('show_consultation_fee')->default(true);
            $table->boolean('show_token_status')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'token_prefix',
                'token_start_number',
                'daily_token_reset',
                'auto_print_token',
                'default_token_status',
                'thermal_paper_size',
                'print_copies',
                'show_logo',
                'show_organization_name',
                'show_patient_id',
                'show_doctor_name',
                'show_date',
                'show_time',
                'show_consultation_fee',
                'show_token_status',
            ]);
        });
    }
};
