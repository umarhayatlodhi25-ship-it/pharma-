<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenCounter extends Model
{
    protected $table = 'token_counters';

    protected $fillable = [
        'queue_key',
        'token_date',
        'current_number',
    ];

    protected $casts = [
        'token_date' => 'date:Y-m-d',
        'current_number' => 'integer',
    ];
}

