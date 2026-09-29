<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    use HasFactory;

    protected $table = 'doctors';

    protected $fillable = [
        'name',
        'specialization',
        'qualification',
        'phone',
        'email',
        'gender',
        'consultation_fee',
        'status',
        'address',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'consultation_fee' => 'decimal:2',
        'is_active'        => 'boolean',
    ];

    /**
     * Booted lifecycle events to keep status and is_active perfectly synchronized.
     */
    protected static function booted(): void
    {
        static::saving(function (Doctor $doctor) {
            if (!empty($doctor->status)) {
                $doctor->is_active = ($doctor->status === 'active');
            } elseif ($doctor->isDirty('is_active')) {
                $doctor->status = $doctor->is_active ? 'active' : 'inactive';
            } else {
                $doctor->status = 'active';
                $doctor->is_active = true;
            }
        });
    }

    /**
     * Scope: Only active doctors.
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'active')->where('is_active', true);
        });
    }

    /**
     * Scope: Only inactive doctors.
     */
    public function scopeInactive($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'inactive')->orWhere('is_active', false);
        });
    }

    /**
     * Relationship: Tokens assigned to this doctor.
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(PatientToken::class, 'doctor_id');
    }

    /**
     * Alias relationship as requested in specifications.
     */
    public function opdTokens(): HasMany
    {
        return $this->tokens();
    }

    /**
     * Check if doctor has any historical OPD records.
     */
    public function hasTokens(): bool
    {
        return $this->tokens()->exists();
    }

    /**
     * Accessor: Formatted consultation fee (e.g. PKR 1,000)
     */
    public function getFormattedFeeAttribute(): string
    {
        return 'PKR ' . number_format($this->consultation_fee, 0);
    }
}
