<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'surname',
        'email',
        'hidden',
        'phone',
        'role',
        'receives_invoice_copy',
        'is_main_contact',
        'receives_notifications',
        'primary',
    ];

    protected $casts = [
        'primary' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($contact) {
            // Se è il primo contatto per quel client o non c'è nessun primary, impostalo come primary
            $hasPrimary = self::where('client_id', $contact->client_id)
                ->where('primary', true)
                ->exists();

            if (!$hasPrimary) {
                $contact->primary = true;
            }
        });

        static::saved(function ($contact) {
            // Se questo contatto è stato salvato come primary
            if ($contact->primary) {
                // Imposta tutti gli altri dello stesso client come non primary
                self::where('client_id', $contact->client_id)
                    ->where('id', '!=', $contact->id)
                    ->update(['primary' => false]);
            }

            // Se il contatto ha un'email e il client non ha ancora un dominio, imposta il dominio
            if ($contact->email && str_contains($contact->email, '@')) {
                $client = $contact->client;
                if ($client && !$client->domain) {
                    $domain = strtolower(substr(strrchr($contact->email, '@'), 1));
                    
                    // Verifica se non è un dominio personale
                    $personalDomains = [
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
                    ];
                    
                    if (!in_array($domain, $personalDomains)) {
                        $client->update(['domain' => $domain]);
                    }
                }
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}