<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Only create initial admin if NO users exist in the system at all
        if (\App\Models\User::withTrashed()->count() === 0) {
            \App\Models\User::create([
                'name' => 'Admin User',
                'email' => 'admin@pharmacy.com',
                'role' => 'admin',
                'password' => 'admin123',
            ]);
        }

        // 2. Seed Clean Master Categories & Units (NO demo medicines or transactions)
        $this->call([
            DemoDataSeeder::class,
        ]);

        // 3. Restore all users saved in cloud
        try {
            \App\Services\FirebaseService::syncFirebaseUsersToLocal();
        } catch (\Throwable $e) {
            // ignore network issues during local/build
        }
    }
}
