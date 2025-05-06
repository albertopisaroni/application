<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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

    public function getLogoUrlAttribute(): string
    {
        $email = $this->primaryContact?->email;

        if (!$email || !str_contains($email, '@')) {
            return $this->fallbackAvatar();
        }

        $domain = strtolower(substr(strrchr($email, "@"), 1));

        $excludedDomains = [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'live.com', 'aol.com',
            'outlook.com', 'icloud.com', 'mail.com', 'msn.com', 'protonmail.com',
            'tiscali.it', 'libero.it', 'virgilio.it', 'fastwebnet.it', 'tim.it',
            'email.it', 'inwind.it', 'vodafone.it', 'poste.it'
        ];

        if (in_array($domain, $excludedDomains)) {
            return $this->fallbackAvatar();
        }

        return Cache::remember("client_logo_url_{$domain}", 86400, function () use ($domain) {
            $brandfetch = "https://cdn.brandfetch.io/{$domain}/w/64/h/64/fallback/404?c=1idF_Jzc3poux8xtCJk";
            if ($this->urlExists($brandfetch)) {
                return $brandfetch;
            }

            $clearbit = "https://logo.clearbit.com/{$domain}";
            if ($this->urlExists($clearbit)) {
                return $clearbit;
            }

            return $this->fallbackAvatar();
        });
    }

    protected function fallbackAvatar(): string
    {
        return 'https://ui-avatars.com/api/?format=svg&name=' . urlencode($this->name);
    }

    protected function urlExists(string $url): bool
    {
        try {
            $response = Http::timeout(2)->head($url);
            return $response->ok();
        } catch (\Exception $e) {
            return false;
        }
    }
}