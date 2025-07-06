<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Client extends Model
{
    protected $fillable = [
        'company_id',
        'stripe_account_id',
        'stripe_customer_id',
        'origin',
        'name',
        'address',
        'cap',
        'city',
        'province',
        'country',
        'piva',
        'sdi',
        'pec',
        'email',
        'phone',
        'hidden',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function contacts()
    {
        return $this->hasMany(\App\Models\Contact::class);
    }

    public function primaryContact()
    {
        return $this->hasOne(Contact::class)->where('primary', true);
    }

    public function primaryContactDomain(): ?string
    {
        $email = $this->primaryContact?->email;

        if (!$email || !str_contains($email, '@')) {
            return null;
        }

        return strtolower(substr(strrchr($email, "@"), 1));
    }

    protected function isPersonalEmailDomain($domain): bool
    {
        return in_array($domain, [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'live.com', 'aol.com', 'outlook.it',
            'outlook.com', 'icloud.com', 'mail.com', 'msn.com', 'protonmail.com',
            'tiscali.it', 'libero.it', 'virgilio.it', 'fastwebnet.it', 'tim.it', 'tin.it',
            'email.it', 'inwind.it', 'vodafone.it', 'poste.it'
        ]); 
    }

    public function getLogoAttribute(): string
    {
        $domain = $this->primaryContactDomain();
    
        if (!$domain || $this->isPersonalEmailDomain($domain)) {
            return 'https://ui-avatars.com/api/?format=svg&name=' . urlencode($this->name);
        }
    
        return MetaDomain::findOrCreateByDomain($domain)->logo_url;
    }
}