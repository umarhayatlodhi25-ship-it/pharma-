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
        // Aliases for Phase 3.2 schema
        'visit_date',
        'doctor_fee',
        'fee_type',
        'final_amount',
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
        'called_at'                 => 'datetime',
        'completed_at'              => 'datetime',
    ];

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
        return str_pad((string) $this->token_number, 3, '0', STR_PAD_LEFT);
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

    public function getVisitTimeAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('h:i A') : now()->format('h:i A');
    }

    public function getDisplayFeeAttribute(): string
    {
        $type = strtolower($this->payment_type ?? $this->fee_type ?? 'paid');
        if ($type === 'free' || floatval($this->charged_amount) == 0) {
            return 'FREE';
        }
        return 'PKR ' . number_format($this->charged_amount ?? $this->consultation_fee, 0);
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
    public static function generateNextTokenNumber(string $date): int
    {
        $max = self::where('token_date', $date)->max('token_number');
        return ($max ? intval($max) : 0) + 1;
    }
}
