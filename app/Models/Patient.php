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
}
