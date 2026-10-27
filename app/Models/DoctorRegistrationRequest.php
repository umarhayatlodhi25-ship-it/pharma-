<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorRegistrationRequest extends Model
{
    use HasFactory;

    protected $table = 'doctor_registration_requests';

    protected $fillable = [
        'doctor_id',
        'user_id',
        'doctor_identifier',
        'name',
        'email',
        'phone',
        'license_number',
        'identity_document_type',
        'is_verified',
        'verification_notes',
        'status',
        'admin_remarks',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'terms_accepted_at',
        'ip_address',
    ];

    protected $casts = [
        'is_verified'       => 'boolean',
        'reviewed_at'       => 'datetime',
        'terms_accepted_at' => 'datetime',
    ];

    /**
     * Relationship: The existing doctor profile this request links to.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Relationship: The portal user account created or linked for this request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship: The administrator who reviewed (approved/rejected/suspended) this request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope: Pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: Rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope: Suspended requests.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Badge CSS classes for UI styling.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'approved'  => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'rejected'  => 'bg-rose-100 text-rose-800 border-rose-300',
            'suspended' => 'bg-slate-200 text-slate-800 border-slate-300',
            default     => 'bg-amber-100 text-amber-800 border-amber-300',
        };
    }
}
