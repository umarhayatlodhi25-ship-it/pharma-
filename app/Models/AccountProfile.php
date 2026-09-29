<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AccountProfile extends Model
{
    use HasFactory;

    protected $table = 'account_profiles';

    protected $fillable = [
        'account_name',
        'logo',
        'phone',
        'email',
        'address',
        'city',
        'website',
        'registration_number',
        'footer_text',
        'currency',
        'timezone',
    ];

    /**
     * In-memory cache for the current singleton profile.
     */
    protected static ?self $cachedCurrent = null;

    protected static function booted(): void
    {
        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }

    /**
     * Clear in-memory cache.
     */
    public static function clearCache(): void
    {
        static::$cachedCurrent = null;
    }

    /**
     * Retrieve the current centralized account profile (singleton).
     */
    public static function current(): self
    {
        if (static::$cachedCurrent !== null) {
            return static::$cachedCurrent;
        }

        $profile = static::first();

        if (!$profile) {
            $profile = static::create([
                'account_name'        => config('app.name', 'Pharmacy & Hospital Management'),
                'currency'            => 'PKR',
                'timezone'            => 'Asia/Karachi',
                'footer_text'         => 'Thank you for choosing our healthcare services.',
            ]);
        }

        return static::$cachedCurrent = $profile;
    }

    /**
     * Check if a valid logo is assigned and file exists.
     */
    public function hasLogo(): bool
    {
        return !empty($this->logo) && Storage::disk('public')->exists($this->logo);
    }

    /**
     * Get accessible URL for the logo.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->hasLogo()) {
            return Storage::disk('public')->url($this->logo);
        }

        return null;
    }

    /**
     * Formatted address combining address and city.
     */
    public function getFormattedAddressAttribute(): ?string
    {
        $parts = array_filter([$this->address, $this->city]);
        return count($parts) > 0 ? implode(', ', $parts) : null;
    }
}
