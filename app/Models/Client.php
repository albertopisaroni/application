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
        'domain',
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

    protected static function booted()
    {
        static::updated(function ($client) {
            // Se il client ha un dominio e una P.IVA, aggiorna il MetaPiva corrispondente
            if ($client->domain && $client->piva) {
                $metaPiva = \App\Models\MetaPiva::where('piva', $client->piva)
                    ->whereNull('domain')
                    ->first();
                
                if ($metaPiva) {
                    $metaPiva->update(['domain' => $client->domain]);
                }
            }
        });

        static::saving(function ($client) {
            // Pulisci il dominio prima di salvarlo
            if ($client->domain) {
                $client->domain = self::cleanDomain($client->domain);
            }
        });
    }

    /**
     * Pulisce un dominio rimuovendo protocolli, www e path
     */
    public static function cleanDomain(?string $domain): ?string
    {
        if (!$domain) {
            return null;
        }

        // Rimuovi protocolli (http://, https://, ftp://, etc.)
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/^http:\/\//', '', $domain);
        $domain = preg_replace('/^https:\/\//', '', $domain);
        $domain = preg_replace('/^ftp:\/\//', '', $domain);

        // Rimuovi www. se presente
        $domain = preg_replace('/^www\./', '', $domain);

        // Rimuovi path e query string (tutto dopo il primo /)
        $domain = strtok($domain, '/');

        // Rimuovi spazi e caratteri non validi
        $domain = trim($domain);

        // Verifica che sia un dominio valido
        if (empty($domain) || !filter_var('http://' . $domain, FILTER_VALIDATE_URL)) {
            return null;
        }

        return strtolower($domain);
    }

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
            'email.it', 'inwind.it', 'vodafone.it', 'poste.it',
            'alice.it', 'aruba.it', 'fastweb.it', 'infinito.it', 'jumpy.it', 'katamail.com',
            'libero.it', 'mclink.it', 'pec.it', 'pec.it', 'register.it', 'supereva.it',
            'tiscali.it', 'virgilio.it', 'webmail.it', 'wind.it', 'yahoo.it', 'ymail.com',
            'hotmail.it', 'live.it', 'msn.it', 'outlook.it', 'windowslive.com',
            'gmx.com', 'gmx.it', 'web.de', 'freenet.de', 't-online.de',
            'laposte.net', 'orange.fr', 'free.fr', 'wanadoo.fr',
            'terra.com', 'terra.es', 'yahoo.es', 'gmail.es',
            'rediffmail.com', 'sify.com', 'indiatimes.com',
            'naver.com', 'daum.net', 'hanmail.net',
            'qq.com', '163.com', '126.com', 'sina.com',
            'yandex.ru', 'mail.ru', 'rambler.ru',
            'seznam.cz', 'centrum.cz',
            'wp.pl', 'onet.pl', 'interia.pl',
            'abv.bg', 'mail.bg',
            'seznam.cz', 'centrum.cz',
            'wp.pl', 'onet.pl', 'interia.pl',
            'abv.bg', 'mail.bg'
        ]); 
    }

    public function getLogoAttribute(): string
    {
        // Usa il campo domain se presente, altrimenti fallback al dominio dell'email del contatto primario
        $domain = $this->domain ?: $this->primaryContactDomain();
    
        if (!$domain || $this->isPersonalEmailDomain($domain)) {
            return 'https://ui-avatars.com/api/?format=svg&name=' . urlencode($this->name);
        }
    
        return MetaDomain::findOrCreateByDomain($domain)->logo_url;
    }
}