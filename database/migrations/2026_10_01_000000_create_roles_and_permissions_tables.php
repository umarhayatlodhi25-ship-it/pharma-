<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('feature'); // e.g., 'manage_medicines', 'pos_access'
            $table->timestamps();
            
            $table->unique(['role_id', 'feature']);
        });
        
        // Let's create some default roles
        $roles = [
            ['name' => 'Admin', 'slug' => 'admin'],
            ['name' => 'Pharmacist', 'slug' => 'pharmacist'],
            ['name' => 'Cashier', 'slug' => 'cashier'],
            ['name' => 'Hospital', 'slug' => 'hospital'],
            ['name' => 'Assistant Admin', 'slug' => 'assistant_admin'],
        ];
        
        foreach ($roles as $role) {
            \Illuminate\Support\Facades\DB::table('roles')->insert(array_merge($role, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
    }
};
