<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $table = 'sales';

    protected $fillable = [
        'invoice_number',
        'user_id',
        'customer_id',
        'subtotal',
        'discount',
        'tax',
        'total_amount',
        'paid_amount',
        'change_amount',
        'payment_method',
        'payment_status',
    ];

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER
    |--------------------------------------------------------------------------
    */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            Customer::class,
            'customer_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | USER / CASHIER
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SALE ITEMS
    |--------------------------------------------------------------------------
    */

    public function items(): HasMany
    {
        return $this->hasMany(
            SaleItem::class,
            'sale_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENTS
    |--------------------------------------------------------------------------
    */

    public function payments(): HasMany
    {
        return $this->hasMany(
            CustomerPayment::class,
            'sale_id'
        );
    }
}