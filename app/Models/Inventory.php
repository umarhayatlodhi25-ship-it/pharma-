<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'medicine_id',
        'total_base_quantity',
        'reserved_base_quantity',
        'available_base_quantity',
    ];

    protected $casts = [
        'total_base_quantity' => 'decimal:4',
        'reserved_base_quantity' => 'decimal:4',
        'available_base_quantity' => 'decimal:4',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class)->withTrashed();
    }

    /**
     * Recalculate available stock
     */
    public function recalculateAvailable(): void
    {
        $this->available_base_quantity = max(0, $this->total_base_quantity - $this->reserved_base_quantity);
        $this->save();
    }
}
