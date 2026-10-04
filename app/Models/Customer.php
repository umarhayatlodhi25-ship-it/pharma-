<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'email', 'address'];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function getOutstandingBalanceAttribute()
    {
        $totalBilled = $this->sales()->sum('total_amount');
        $totalPaid = $this->sales()->sum('paid_amount');
        return round($totalBilled - $totalPaid, 2);
    }
}