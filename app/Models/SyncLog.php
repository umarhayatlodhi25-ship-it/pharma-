<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    public $timestamps = false;
    protected $table = 'sync_logs';

    protected $fillable = [
        'sync_outbox_id',
        'event_type',
        'status',
        'response_code',
        'response_body',
        'execution_time_ms',
        'created_at',
    ];

    protected $casts = [
        'response_code' => 'integer',
        'execution_time_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function outbox(): BelongsTo
    {
        return $this->belongsTo(SyncOutbox::class, 'sync_outbox_id');
    }
}

