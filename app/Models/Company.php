<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\ApiToken;

use App\Models\InvoiceNumbering;

class Company extends Model
{
    protected $guarded = ['slug'];

    protected static function booted()
    {
        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }

            InvoiceNumbering::create([
                'company_id'     => $company->id,
                'type'           => 'standard',
                'prefix'         => null,  // Nessun prefisso per la numerazione standard
                'current_number' => 0,     // Partenza da 0 (al prossimo utilizzo diventa 1)
                'name'           => 'Standard',
            ]);
    
        });

        static::updating(function ($company) {
            if ($company->isDirty('name') && empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
        });
    }    

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('permissions')->withTimestamps();
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function clients()
    {
        return $this->hasMany(\App\Models\Client::class);
    }

    public function invoiceNumberings()
    {
        return $this->hasMany(\App\Models\InvoiceNumbering::class);
    }
    
    public function paymentMethods()
    {
        return $this->hasMany(\App\Models\PaymentMethod::class);
    }

    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice::class);
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    public function emailAccounts()
    {
        return $this->hasMany(EmailAccount::class);
    }

    public function stripeAccounts()
    {
        return $this->hasMany(StripeAccount::class);
    }

    public function defaultStripeAccount()
    {
        return $this->hasOne(StripeAccount::class)->where('default', true);
    }
}