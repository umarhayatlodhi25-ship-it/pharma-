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
        Schema::create('account_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->string('logo')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('website', 150)->nullable();
            $table->string('registration_number', 100)->nullable();
            $table->text('footer_text')->nullable();
            $table->string('currency', 10)->default('PKR');
            $table->string('timezone', 50)->default('Asia/Karachi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_profiles');
    }
};
