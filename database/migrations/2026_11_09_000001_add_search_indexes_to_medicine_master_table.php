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
        Schema::table('medicine_master', function (Blueprint $table) {
            // Ensure unique index on medicine_id
            if (!Schema::hasIndex('medicine_master', 'medicine_master_medicine_id_unique', 'unique') &&
                !Schema::hasIndex('medicine_master', ['medicine_id'], 'unique')) {
                $table->unique('medicine_id', 'medicine_master_medicine_id_unique');
            }

            // Ensure standalone index on medicine_name
            if (!Schema::hasIndex('medicine_master', 'medicine_master_medicine_name_index') &&
                !Schema::hasIndex('medicine_master', 'medicine_master_name_standalone_idx') &&
                !Schema::hasIndex('medicine_master', ['medicine_name'])) {
                $table->index('medicine_name', 'medicine_master_medicine_name_index');
            }

            // Ensure standalone index on brand_name
            if (!Schema::hasIndex('medicine_master', 'medicine_master_brand_name_index') &&
                !Schema::hasIndex('medicine_master', 'medicine_master_brand_standalone_idx') &&
                !Schema::hasIndex('medicine_master', ['brand_name'])) {
                $table->index('brand_name', 'medicine_master_brand_name_index');
            }

            // Ensure standalone index on generic_name
            if (!Schema::hasIndex('medicine_master', 'medicine_master_generic_name_index') &&
                !Schema::hasIndex('medicine_master', 'medicine_master_generic_standalone_idx') &&
                !Schema::hasIndex('medicine_master', ['generic_name'])) {
                $table->index('generic_name', 'medicine_master_generic_name_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medicine_master', function (Blueprint $table) {
            if (Schema::hasIndex('medicine_master', 'medicine_master_medicine_name_index')) {
                $table->dropIndex('medicine_master_medicine_name_index');
            }
            if (Schema::hasIndex('medicine_master', 'medicine_master_brand_name_index')) {
                $table->dropIndex('medicine_master_brand_name_index');
            }
            if (Schema::hasIndex('medicine_master', 'medicine_master_generic_name_index')) {
                $table->dropIndex('medicine_master_generic_name_index');
            }
        });
    }
};
