<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    use HasFactory;

    protected $table = 'prescriptions';

    protected $fillable = [
        'prescription_number',
        'patient_id',
        'doctor_id',
        'patient_token_id',
        'diagnosis',
        'clinical_notes',
        'follow_up_date',
        'follow_up_instructions',
        'status',
        'is_finalized',
        'created_by',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'is_finalized'   => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Prescription $prescription) {
            if (empty($prescription->prescription_number)) {
                $prescription->prescription_number = self::generatePrescriptionNumber();
            }
        });
    }

    /**
     * Generate sequential prescription number (e.g. RX-00001).
     */
    public static function generatePrescriptionNumber(): string
    {
        $latest = self::orderBy('id', 'desc')->first();
        $nextNum = 1;

        if ($latest && !empty($latest->prescription_number)) {
            if (preg_match('/RX-(\d+)/i', $latest->prescription_number, $matches)) {
                $nextNum = intval($matches[1]) + 1;
            } else {
                $nextNum = $latest->id + 1;
            }
        }

        do {
            $formatted = 'RX-' . str_pad((string) $nextNum, 5, '0', STR_PAD_LEFT);
            $exists = self::where('prescription_number', $formatted)->exists();
            if ($exists) {
                $nextNum++;
            }
        } while ($exists);

        return $formatted;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(PatientToken::class, 'patient_token_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class, 'prescription_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedDateAttribute(): string
    {
        return format_app_datetime($this->created_at, 'd M, Y h:i A');
    }
}
