<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function (User $user) {
            try {
                \App\Services\FirebaseService::syncUser($user);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Firebase sync failed on saved event: ' . $e->getMessage());
            }
        });

        static::deleted(function (User $user) {
            try {
                if (method_exists($user, 'isForceDeleting') && $user->isForceDeleting()) {
                    \App\Services\FirebaseService::deleteUser($user->email);
                } else {
                    \App\Services\FirebaseService::syncUser($user);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Firebase delete failed on deleted event: ' . $e->getMessage());
            }
        });

        static::restored(function (User $user) {
            try {
                \App\Services\FirebaseService::syncUser($user);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Firebase restore failed on restored event: ' . $e->getMessage());
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is Admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is Pharmacist.
     */
    public function isPharmacist(): bool
    {
        return $this->role === 'pharmacist';
    }

    /**
     * Check if user is Cashier.
     */
    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    /**
     * Check if user has specific role or one of given roles.
     */
    public function hasRole(string|array $roles): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $roles = is_array($roles) ? $roles : explode(',', $roles);
        return in_array($this->role, array_map('trim', $roles), true);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // Cache role permissions for the request lifecycle
        static $rolePermissions = [];
        
        if (!array_key_exists($this->role, $rolePermissions)) {
            $rolePermissions[$this->role] = \App\Models\RolePermission::whereHas('role', function ($q) {
                $q->where('slug', $this->role);
            })->pluck('feature')->toArray();
        }

        return in_array($permission, $rolePermissions[$this->role], true);
    }
}
