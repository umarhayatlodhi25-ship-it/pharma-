<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenCallLog extends Model
{
    protected $table = 'token_call_logs';

    protected $fillable = [
        'patient_token_id',
        'action',
        'from_status',
        'to_status',
        'doctor_id',
        'room_number',
        'user_id',
        'notes',
        'called_at',
    ];

    protected $casts = [
        'called_at' => 'datetime',
    ];

    public function patientToken(): BelongsTo
    {
        return $this->belongsTo(PatientToken::class, 'patient_token_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

