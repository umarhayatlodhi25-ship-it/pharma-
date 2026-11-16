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
     * Clear in-memory and application cache.
     */
    public static function clearCache(): void
    {
        static::$cachedCurrent = null;
        try {
            \Illuminate\Support\Facades\Cache::forget('account_profile_current');
        } catch (\Throwable $e) {
            // Non-blocking in early bootstraps
        }
    }

    /**
     * Retrieve the current centralized account profile (singleton).
     */
    public static function current(): self
    {
        if (static::$cachedCurrent !== null) {
            return static::$cachedCurrent;
        }

        try {
            $profile = \Illuminate\Support\Facades\Cache::remember('account_profile_current', 300, function () {
                return static::first();
            });
        } catch (\Throwable $e) {
            $profile = static::first();
        }

        if (!$profile) {
            $profile = static::firstOrCreate([], [
                'account_name'        => config('app.name', 'PHARMACY MANAGEMENT'),
                'address'             => 'Landhi, Karachi, Pakistan',
                'phone'               => '0300-1234567',
                'currency'            => 'PKR',
                'timezone'            => 'Asia/Karachi',
                'footer_text'         => 'Software Developed By Pharmacy Team',
                'daily_token_reset'   => true,
            ]);
        }

        return static::$cachedCurrent = $profile;
    }

    /**
     * Receipt business name with fallback.
     */
    public function getReceiptBusinessNameAttribute(): string
    {
        return !empty(trim((string)$this->account_name)) ? $this->account_name : 'PHARMACY MANAGEMENT';
    }

    /**
     * Receipt address with fallback.
     */
    public function getReceiptAddressAttribute(): ?string
    {
        $addr = $this->formatted_address;
        return !empty(trim((string)$addr)) ? $addr : null;
    }

    /**
     * Receipt phone with fallback.
     */
    public function getReceiptPhoneAttribute(): ?string
    {
        return !empty(trim((string)$this->phone)) ? $this->phone : null;
    }

    /**
     * Receipt footer text with fallback.
     */
    public function getReceiptFooterAttribute(): string
    {
        return !empty(trim((string)$this->footer_text)) ? $this->footer_text : 'Software Developed By Pharmacy Team';
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
