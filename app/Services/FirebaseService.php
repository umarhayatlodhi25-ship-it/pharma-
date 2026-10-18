<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    /**
     * Get base Firebase Realtime Database URL.
     */
    protected static function getDbUrl(): string
    {
        $url = config('services.firebase.database_url', 'https://pharmacymanagesystem-default-rtdb.firebaseio.com');
        return rtrim($url, '/');
    }

    /**
     * Save/Update a user in Firebase Realtime Database.
     */
    public static function syncUser(User $user): bool
    {
        if (app()->environment('testing') || str_contains($user->email ?? '', '@example.')) {
            return false;
        }

        try {
            $url = self::getDbUrl() . "/users/" . md5(strtolower(trim($user->email))) . ".json";
            
            $payload = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => strtolower(trim($user->email)),
                'role' => $user->role ?? 'admin',
                'password' => $user->password,
                'deleted_at' => $user->deleted_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];

            $response = Http::timeout(4)->put($url, $payload);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Firebase syncUser error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete user from Firebase.
     */
    public static function deleteUser(string $email): bool
    {
        try {
            $url = self::getDbUrl() . "/users/" . md5(strtolower(trim($email))) . ".json";
            $response = Http::timeout(4)->delete($url);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Firebase deleteUser error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch all users from Firebase Realtime Database.
     */
    public static function getUsersFromFirebase(): array
    {
        try {
            $url = self::getDbUrl() . "/users.json";
            $response = Http::timeout(4)->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                return is_array($data) ? $data : [];
            }
        } catch (\Throwable $e) {
            Log::error('Firebase getUsersFromFirebase error: ' . $e->getMessage());
        }
        return [];
    }

    /**
     * Sync Firebase Users into local database if missing.
     */
    public static function syncFirebaseUsersToLocal(): void
    {
        try {
            $fbUsers = self::getUsersFromFirebase();
            if (empty($fbUsers)) {
                return;
            }

            foreach ($fbUsers as $key => $fbData) {
                if (empty($fbData['email'])) continue;

                $email = strtolower(trim($fbData['email']));
                if (str_contains($email, '@example.')) continue;

                $existingUser = User::withTrashed()->where('email', $email)->first();

                if ($existingUser) {
                    // Update name, role, password or trash status if changed remotely
                    $dirty = false;
                    if (!empty($fbData['name']) && $existingUser->name !== $fbData['name']) {
                        $existingUser->name = $fbData['name'];
                        $dirty = true;
                    }
                    if (!empty($fbData['role']) && $existingUser->role !== $fbData['role']) {
                        $existingUser->role = $fbData['role'];
                        $dirty = true;
                    }
                    if (!empty($fbData['password']) && $existingUser->password !== $fbData['password']) {
                        $existingUser->setRawAttributes(array_merge($existingUser->getAttributes(), [
                            'password' => $fbData['password'],
                        ]));
                        $dirty = true;
                    }
                    if (!empty($fbData['deleted_at']) && !$existingUser->trashed()) {
                        $existingUser->deleted_at = $fbData['deleted_at'];
                        $dirty = true;
                    } elseif (empty($fbData['deleted_at']) && $existingUser->trashed()) {
                        $existingUser->deleted_at = null;
                        $dirty = true;
                    }
                    if ($dirty) {
                        $existingUser->saveQuietly();
                    }
                    continue;
                }

                $user = new User();
                $user->name = $fbData['name'] ?? 'Pharmacy User';
                $user->email = $email;
                $user->role = $fbData['role'] ?? 'admin';
                
                // Preserve raw password hash
                if (!empty($fbData['password'])) {
                    $user->setRawAttributes(array_merge($user->getAttributes(), [
                        'name' => $fbData['name'] ?? 'Pharmacy User',
                        'email' => $email,
                        'role' => $fbData['role'] ?? 'admin',
                        'password' => $fbData['password'],
                    ]));
                } else {
                    $user->password = 'admin123';
                }

                if (!empty($fbData['deleted_at'])) {
                    $user->deleted_at = $fbData['deleted_at'];
                }

                $user->saveQuietly();
            }
        } catch (\Throwable $e) {
            Log::error('Firebase syncFirebaseUsersToLocal error: ' . $e->getMessage());
        }
    }

    /**
     * Ensure local database is seeded with default users.
     */
    public static function ensureDatabaseSeeded(): void
    {
        try {
            self::syncFirebaseUsersToLocal();

            if (User::withTrashed()->count() === 0) {
                $admin = new User();
                $admin->name = 'Muhammad Faizan Khan Lodhi';
                $admin->email = 'admin@pharmacy.com';
                $admin->role = 'admin';
                $admin->password = 'admin123';
                $admin->saveQuietly();
            }
        } catch (\Throwable $e) {
            Log::error('ensureDatabaseSeeded error: ' . $e->getMessage());
        }
    }
}
