<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'stripe_account_id',
        'stripe_charge_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'subscription_id',
    ];

    public function stripeAccount()
    {
        return $this->belongsTo(StripeAccount::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}