<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'client_id',
        'stripe_subscription_id',
        'price_id',
        'status',
        'start_date',
        'current_period_end',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function price()
    {
        return $this->belongsTo(Price::class);
    }
}