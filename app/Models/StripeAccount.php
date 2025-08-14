<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StripeAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'stripe_user_id',
        'account_name',
        'access_token',
        'refresh_token',
        'default',
        'invoice_numbering_id',  // aggiunto
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoiceNumbering()
    {
        return $this->belongsTo(InvoiceNumbering::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}