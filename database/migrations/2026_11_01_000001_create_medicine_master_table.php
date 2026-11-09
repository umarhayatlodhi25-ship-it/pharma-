<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_master', function (Blueprint $table) {
            $table->id();
            $table->string('medicine_id')->unique();
            $table->string('medicine_name');
            $table->string('brand_name')->nullable();
            $table->string('generic_name')->nullable();
            $table->text('composition')->nullable();
            $table->string('strength')->nullable();
            $table->string('dosage_form')->nullable();
            $table->string('category')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('pack_size')->nullable();
            $table->string('stock_unit')->nullable();
            $table->string('source')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('verification_status')->default('pending');
            $table->timestamps();

            $table->index(['medicine_name', 'brand_name', 'generic_name'], 'medicine_master_name_index');
            $table->index('manufacturer', 'medicine_master_manufacturer_index');
            $table->index('verification_status', 'medicine_master_verification_index');
            $table->index('source_reference', 'medicine_master_source_reference_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_master');
    }
};
