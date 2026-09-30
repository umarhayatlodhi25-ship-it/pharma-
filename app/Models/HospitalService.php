<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HospitalService extends Model
{
    use HasFactory;

    protected $table = 'hospital_services';

    protected $fillable = [
        'name',
        'code',
        'service_type',
        'default_fee',
        'doctor_share_percentage',
        'hospital_share_percentage',
        'is_active',
        'description',
    ];

    protected $casts = [
        'default_fee'               => 'decimal:2',
        'doctor_share_percentage'   => 'decimal:2',
        'hospital_share_percentage' => 'decimal:2',
        'is_active'                 => 'boolean',
    ];

    /**
     * Scope: Only active services.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Accessor alias for code / service_code.
     */
    public function getServiceCodeAttribute(): ?string
    {
        return $this->code;
    }

    /**
     * Calculate Doctor and Hospital share safely using decimal arithmetic.
     * 
     * Formula:
     * doctor_share = total_amount × doctor_percentage / 100
     * hospital_share = total_amount - doctor_share
     *
     * @param float $collectedAmount
     * @return array{doctor_share: float, hospital_share: float}
     */
    public function calculateSplit(float $collectedAmount): array
    {
        if ($collectedAmount <= 0) {
            return [
                'doctor_share'   => 0.00,
                'hospital_share' => 0.00,
            ];
        }

        $doctorShare = round($collectedAmount * ((float)$this->doctor_share_percentage / 100), 2);
        $hospitalShare = round($collectedAmount - $doctorShare, 2);

        return [
            'doctor_share'   => $doctorShare,
            'hospital_share' => $hospitalShare,
        ];
    }

    /**
     * Relationship: Bills issued for this service.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(HospitalBill::class, 'hospital_service_id');
    }

    /**
     * Relationship: Patient tokens associated with this service.
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(PatientToken::class, 'hospital_service_id');
    }

    /**
     * Formatted display fee.
     */
    public function getFormattedFeeAttribute(): string
    {
        return 'PKR ' . number_format($this->default_fee, 2);
    }
}
