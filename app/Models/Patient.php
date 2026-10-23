<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_number',
        'name',
        'father_husband_name',
        'age',
        'gender',
        'phone',
        'address',
        'cnic',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'age' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Patient $patient) {
            if (empty($patient->patient_number)) {
                $patient->patient_number = self::generatePatientNumber();
            }
        });
    }

    /**
     * Generate the next sequential patient number (e.g. PT-00001, PT-00002).
     */
    public static function generatePatientNumber(): string
    {
        $latest = self::orderBy('id', 'desc')->first();
        $nextNum = 1;

        if ($latest && !empty($latest->patient_number)) {
            if (preg_match('/PT-(\d+)/i', $latest->patient_number, $matches)) {
                $nextNum = intval($matches[1]) + 1;
            } else {
                $nextNum = $latest->id + 1;
            }
        }

        do {
            $formatted = 'PT-' . str_pad((string) $nextNum, 5, '0', STR_PAD_LEFT);
            $exists = self::where('patient_number', $formatted)->exists();
            if ($exists) {
                $nextNum++;
            }
        } while ($exists);

        return $formatted;
    }

    /**
     * Relationship: All tokens issued to this patient.
     */
    public function tokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientToken::class, 'patient_id')->orderBy('token_date', 'desc')->orderBy('token_number', 'desc');
    }

    /**
     * Relationship: All hospital bills for this patient.
     */
    public function bills(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(HospitalBill::class, 'patient_id')->orderBy('bill_date', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Alias for bills relationship.
     */
    public function hospitalBills(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->bills();
    }

    /**
     * Relationship: All payments made by this patient.
     */
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(HospitalBillPayment::class, 'patient_id')->orderBy('payment_date', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Helper: Check if the patient has an active (waiting or called) token for a specific date (defaults to today).
     */
    public function activeTokenToday(?string $date = null): ?PatientToken
    {
        $targetDate = $date ?? now()->toDateString();
        return $this->tokens()
            ->where('token_date', $targetDate)
            ->whereIn('status', ['waiting', 'called', 'in_consultation'])
            ->first();
    }

    /**
     * Helper: Get all unpaid or partially paid hospital bills for this patient.
     */
    public function unpaidBills()
    {
        return $this->bills()
            ->where('due_amount', '>', 0)
            ->orderBy('bill_date', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Calculate current total pending unpaid balance across all hospital bills.
     */
    public function pendingBalance(): float
    {
        return round((float) $this->unpaidBills()->sum('due_amount'), 2);
    }

    /**
     * Accessor for pending balance.
     */
    public function getPendingBalanceAttribute(): float
    {
        return $this->pendingBalance();
    }

    /**
     * Relationship: All hospital account ledger entries for this patient.
     */
    public function hospitalLedgers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(HospitalAccountLedger::class, 'patient_id')->orderBy('entry_date', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Outstanding debt originating specifically from doctor consultations / OPD visits.
     */
    public function consultationOutstanding(): float
    {
        return round((float) $this->unpaidBills()->whereNotNull('patient_token_id')->sum('due_amount'), 2);
    }

    /**
     * Outstanding debt originating specifically from hospital services / procedures.
     */
    public function hospitalServiceOutstanding(): float
    {
        return round((float) $this->unpaidBills()->whereNull('patient_token_id')->sum('due_amount'), 2);
    }

    /**
     * Accessor for consultation outstanding debt.
     */
    public function getConsultationOutstandingAttribute(): float
    {
        return $this->consultationOutstanding();
    }

    /**
     * Accessor for hospital service outstanding debt.
     */
    public function getHospitalServiceOutstandingAttribute(): float
    {
        return $this->hospitalServiceOutstanding();
    }

    /**
     * Get the patient's registration timestamp converted to display timezone (Asia/Karachi).
     */
    public function getCreatedAtLocalAttribute(): ?\Carbon\Carbon
    {
        if (!$this->created_at) {
            return null;
        }

        return function_exists('app_datetime')
            ? app_datetime($this->created_at)
            : $this->created_at->copy()->setTimezone(config('app.display_timezone', 'Asia/Karachi'));
    }

    /**
     * Format the patient registration timestamp in the configured display timezone.
     */
    public function formatRegisteredAt(string $format = 'd M, Y h:i A', string $default = '—'): string
    {
        if (function_exists('format_app_datetime')) {
            return format_app_datetime($this->created_at, $format, $default);
        }

        $local = $this->created_at_local;
        return $local ? $local->format($format) : $default;
    }
}
