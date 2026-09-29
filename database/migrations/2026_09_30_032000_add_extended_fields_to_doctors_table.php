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
        Schema::table('doctors', function (Blueprint $table) {
            $table->string('qualification', 255)->nullable()->after('specialization');
            $table->string('gender', 20)->nullable()->after('email');
            $table->string('status', 20)->default('active')->after('is_active');
            $table->string('address', 500)->nullable()->after('status');
            $table->text('notes')->nullable()->after('address');

            $table->index('name');
            $table->index('specialization');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['specialization']);
            $table->dropIndex(['status']);

            $table->dropColumn([
                'qualification',
                'gender',
                'status',
                'address',
                'notes',
            ]);
        });
    }
};
