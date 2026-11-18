<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SyncOutbox extends Model
{
    protected $table = 'sync_outbox';

    protected $fillable = [
        'uuid',
        'event_type',
        'aggregate_type',
        'aggregate_id',
        'payload',
        'status',
        'attempts',
        'last_error',
        'synced_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'synced_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($item) {
            if (empty($item->uuid)) {
                $item->uuid = (string) Str::uuid();
            }
        });
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'sync_outbox_id');
    }
}

