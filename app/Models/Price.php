<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $fillable = [
        'product_id',
        'stripe_price_id',
        'unit_amount',
        'currency',
        'interval',
        'active',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}