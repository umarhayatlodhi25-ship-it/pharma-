<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineMaster extends Model
{
    use HasFactory;

    protected $table = 'medicine_master';

    protected $fillable = [
        'medicine_id',
        'medicine_name',
        'brand_name',
        'generic_name',
        'composition',
        'strength',
        'dosage_form',
        'category',
        'manufacturer',
        'registration_number',
        'pack_size',
        'stock_unit',
        'source',
        'source_reference',
        'verification_status',
    ];

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('verification_status', 'rejected');
    }
}
