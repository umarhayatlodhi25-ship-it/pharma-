<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientToken extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'patient_tokens';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'hospital_service_id',
        'hospital_bill_id',
        'token_number',
        'token_date',
        'payment_type',
        'consultation_fee',
        'charged_amount',
        'doctor_share_percentage',
        'hospital_share_percentage',
        'doctor_share_amount',
        'hospital_share_amount',
        'discount_amount',
        'free_reason',
        'other_reason',
        'status',
        'called_at',
        'completed_at',
        'notes',
        'previous_due',
        'paid_today',
        'balance_due',
        // Aliases for Phase 3.2 schema
        'visit_date',
        'doctor_fee',
        'fee_type',
        'final_amount',
        // Clinical examination data
        'symptoms',
        'diagnosis',
        'clinical_notes',
        'follow_up_date',
        'follow_up_instructions',
        'blood_pressure',
        'pulse_rate',
        'temperature',
        'weight',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'token_number'              => 'integer',
        'token_date'                => 'date:Y-m-d',
        'consultation_fee'          => 'decimal:2',
        'charged_amount'            => 'decimal:2',
        'doctor_share_percentage'   => 'decimal:2',
        'hospital_share_percentage' => 'decimal:2',
        'doctor_share_amount'       => 'decimal:2',
        'hospital_share_amount'     => 'decimal:2',
        'discount_amount'           => 'decimal:2',
        'previous_due'              => 'decimal:2',
        'paid_today'                => 'decimal:2',
        'balance_due'               => 'decimal:2',
        'called_at'                 => 'datetime',
        'completed_at'              => 'datetime',
        'follow_up_date'            => 'date:Y-m-d',
    ];

    /**
     * Relationship: The prescription created from this consultation token.
     */
    public function prescription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Prescription::class, 'patient_token_id');
    }

    /**
     * Relationship: The patient this token belongs to.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Relationship: The doctor assigned to this token.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Relationship: The hospital service for this token.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(HospitalService::class, 'hospital_service_id');
    }

    /**
     * Relationship: The hospital bill generated for this token.
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(HospitalBill::class, 'hospital_bill_id');
    }

    /**
     * Accessor: Format token number as 3-digit zero-padded string (e.g. 001, 002, 015).
     */
    public function getFormattedTokenNumberAttribute(): string
    {
        $profile = \App\Models\AccountProfile::current();
        $prefix = $profile->token_prefix ?? '';
        $padded = str_pad((string) $this->token_number, 3, '0', STR_PAD_LEFT);
        return $prefix . $padded;
    }

    /**
     * Aliases for Phase 3.2 OPD specifications
     */
    public function getDoctorFeeAttribute()
    {
        return $this->consultation_fee;
    }

    public function setDoctorFeeAttribute($value): void
    {
        $this->attributes['consultation_fee'] = $value;
    }

    public function getFeeTypeAttribute()
    {
        return $this->payment_type;
    }

    public function setFeeTypeAttribute($value): void
    {
        $this->attributes['payment_type'] = $value;
    }

    public function getFinalAmountAttribute()
    {
        return $this->charged_amount;
    }

    public function setFinalAmountAttribute($value): void
    {
        $this->attributes['charged_amount'] = $value;
    }

    public function getVisitDateAttribute()
    {
        return $this->token_date;
    }

    public function setVisitDateAttribute($value): void
    {
        $this->attributes['token_date'] = $value;
    }

    public function getCreatedAtLocalAttribute(): ?\Carbon\Carbon
    {
        return $this->created_at ? app_datetime($this->created_at) : null;
    }

    public function getVisitTimeAttribute(): string
    {
        return format_app_datetime($this->created_at ?: now(), 'h:i A');
    }

    public function formatCreatedAt(string $format = 'd M Y, h:i A', string $default = '—'): string
    {
        return format_app_datetime($this->created_at, $format, $default);
    }

    public function getDisplayFeeAttribute(): string
    {
        $type = strtolower($this->payment_type ?? $this->fee_type ?? 'paid');
        if ($type === 'free') {
            return 'FREE';
        }
        $fee = $this->consultation_fee ?? $this->charged_amount ?? 0;
        return 'PKR ' . number_format((float)$fee, 0);
    }

    public function getPaidTodayAttribute($value)
    {
        if ($value !== null) {
            return (float) $value;
        }
        return (float) ($this->charged_amount ?? 0.00);
    }

    public function getBalanceDueAttribute($value)
    {
        if ($value !== null) {
            return (float) $value;
        }
        return (float) ($this->bill?->due_amount ?? 0.00);
    }

    public function getPreviousDueAttribute($value)
    {
        return $value !== null ? (float) $value : 0.00;
    }

    public function getTotalDueAttribute(): float
    {
        $fee = (float) ($this->consultation_fee ?? $this->charged_amount ?? 0);
        if (strtolower($this->payment_type ?? $this->fee_type ?? '') === 'free') {
            $fee = 0.00;
        }
        return round($fee + (float) ($this->previous_due ?? 0), 2);
    }

    public function getFormattedStatusAttribute(): string
    {
        $status = strtolower($this->status ?? 'waiting');
        if ($status === 'called' || $status === 'in_consultation' || $status === 'in consultation') {
            return 'In Consultation';
        }
        return ucfirst($status);
    }

    /**
     * Scope: Filter by token date (defaults to today).
     */
    public function scopeForDate($query, ?string $date = null)
    {
        $targetDate = $date ?? now()->toDateString();
        return $query->where('token_date', $targetDate);
    }

    /**
     * Scope: Filter by active tokens (waiting, called, or in_consultation).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['waiting', 'called', 'in_consultation']);
    }

    /**
     * Scope: Filter by waiting tokens.
     */
    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    /**
     * Scope: Filter by in-consultation/called tokens.
     */
    public function scopeInConsultation($query)
    {
        return $query->whereIn('status', ['called', 'in_consultation']);
    }

    /**
     * Scope: Filter by completed tokens.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Generate next sequential integer token number for the given date.
     * Restarts at 1 for every new day.
     */
    public static function generateNextTokenNumber(string $date, bool $lock = false): int
    {
        $profile = \App\Models\AccountProfile::current();
        $query = self::query();
        
        if ($profile->daily_token_reset) {
            $query->where('token_date', $date);
        }
        
        if ($lock) {
            // Using orderByDesc()->lockForUpdate()->first() avoids PostgreSQL ERROR:
            // "FOR UPDATE is not allowed with aggregate functions"
            $latest = (clone $query)->orderByDesc('token_number')->lockForUpdate()->first();
            $max = $latest ? $latest->token_number : null;
        } else {
            $max = $query->max('token_number');
        }

        $startNumber = (int) $profile->token_start_number > 0 ? (int) $profile->token_start_number : 1;
        
        return $max ? (intval($max) + 1) : $startNumber;
    }
}
