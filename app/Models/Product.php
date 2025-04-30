<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'stripe_account_id',
        'stripe_product_id',
        'name',
        'active',
    ];

    public function prices()
    {
        return $this->hasMany(Price::class);
    }

    public function stripeAccount()
    {
        return $this->belongsTo(StripeAccount::class);
    }
}