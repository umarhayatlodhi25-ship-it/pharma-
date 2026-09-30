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
        'doctor_share_percentage',
        'hospital_share_percentage',
        'status',
        'address',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'consultation_fee'          => 'decimal:2',
        'doctor_share_percentage'   => 'decimal:2',
        'hospital_share_percentage' => 'decimal:2',
        'is_active'                 => 'boolean',
    ];

    /**
     * Booted lifecycle events to keep status, active state, and hospital revenue share synchronized.
     */
    protected static function booted(): void
    {
        static::saving(function (Doctor $doctor) {
            // Synchronize status and is_active
            if (!empty($doctor->status)) {
                $doctor->is_active = ($doctor->status === 'active');
            } elseif ($doctor->isDirty('is_active')) {
                $doctor->status = $doctor->is_active ? 'active' : 'inactive';
            } else {
                $doctor->status = 'active';
                $doctor->is_active = true;
            }

            // Automatically compute hospital share: 100% - Doctor Share %
            $docShare = isset($doctor->doctor_share_percentage) ? floatval($doctor->doctor_share_percentage) : 70.00;
            $docShare = max(0, min(100, $docShare));
            $doctor->doctor_share_percentage = $docShare;
            $doctor->hospital_share_percentage = round(100.00 - $docShare, 2);
        });
    }

    /**
     * Calculate Doctor and Hospital share safely using decimal arithmetic.
     * 
     * Formula:
     * doctor_amount = consultation_fee × doctor_share_percentage / 100
     * hospital_amount = consultation_fee - doctor_amount
     *
     * @param float|null $amount
     * @return array{doctor_amount: float, hospital_amount: float}
     */
    public function calculateConsultationSplit(?float $amount = null): array
    {
        $base = ($amount !== null) ? (float) $amount : (float) $this->consultation_fee;
        if ($base <= 0) {
            return [
                'doctor_amount'   => 0.00,
                'hospital_amount' => 0.00,
            ];
        }

        $docPct = (float) ($this->doctor_share_percentage ?? 70.00);
        $docAmount = round($base * ($docPct / 100), 2);
        $hospAmount = round($base - $docAmount, 2);

        return [
            'doctor_amount'   => $docAmount,
            'hospital_amount' => $hospAmount,
        ];
    }

    /**
     * Accessor: Standard Doctor Share Amount based on Doctor Consultation Fee.
     */
    public function getDoctorShareAmountAttribute(): float
    {
        return $this->calculateConsultationSplit()['doctor_amount'];
    }

    /**
     * Accessor: Standard Hospital Share Amount based on Doctor Consultation Fee.
     */
    public function getHospitalShareAmountAttribute(): float
    {
        return $this->calculateConsultationSplit()['hospital_amount'];
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
     * Relationship: Hospital bills associated with this doctor.
     */
    public function hospitalBills(): HasMany
    {
        return $this->hasMany(HospitalBill::class, 'doctor_id');
    }

    /**
     * Relationship: Financial ledger entries for this doctor.
     */
    public function ledgers(): HasMany
    {
        return $this->hasMany(DoctorLedger::class, 'doctor_id')->orderBy('entry_date', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Relationship: Settlement payouts made to this doctor.
     */
    public function settlements(): HasMany
    {
        return $this->hasMany(DoctorSettlement::class, 'doctor_id')->orderBy('settlement_date', 'desc')->orderBy('id', 'desc');
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

    /**
     * Total earned revenue share by this doctor from paid patient services.
     */
    public function getTotalEarnedAttribute(): float
    {
        return (float) $this->ledgers()->where('transaction_type', 'credit')->sum('amount');
    }

    /**
     * Total payout settlements already paid to this doctor.
     */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->ledgers()->where('transaction_type', 'debit')->sum('amount');
    }

    /**
     * Current outstanding payable balance owed to this doctor.
     */
    public function getCurrentPayableAttribute(): float
    {
        return round(max(0.00, $this->total_earned - $this->total_paid), 2);
    }

    public function getFormattedCurrentPayableAttribute(): string
    {
        return 'PKR ' . number_format($this->current_payable, 2);
    }

    public function getFormattedTotalEarnedAttribute(): string
    {
        return 'PKR ' . number_format($this->total_earned, 2);
    }

    public function getFormattedTotalPaidAttribute(): string
    {
        return 'PKR ' . number_format($this->total_paid, 2);
    }
}

