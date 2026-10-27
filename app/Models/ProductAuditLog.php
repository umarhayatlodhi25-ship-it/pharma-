<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAuditLog extends Model
{
    use HasFactory;

    protected $table = 'product_audit_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'affected_product_ids',
        'summary',
        'status',
    ];

    protected $casts = [
        'affected_product_ids' => 'array',
        'summary' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
