<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'stock_movements';

    public $timestamps = false;

    protected $fillable = [
        'medicine_id',
        'batch_id',
        'type',
        'reference_id',
        'reference_type',
        'selected_unit_id',
        'quantity',
        'conversion_to_base',
        'base_quantity',
        'previous_stock',
        'new_stock',
        'user_id',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'conversion_to_base' => 'decimal:4',
        'base_quantity' => 'decimal:4',
        'previous_stock' => 'decimal:4',
        'new_stock' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class)->withTrashed();
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MedicineBatch::class, 'batch_id');
    }

    public function selectedUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'selected_unit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForMedicine($query, $medicineId)
    {
        return $query->where('medicine_id', $medicineId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }
}
