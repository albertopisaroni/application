<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaPiva extends Model
{
    protected $fillable = [
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
    ];

    protected static function booted()
    {
        static::created(function ($metaPiva) {
            // Se il MetaPiva non ha un dominio, cerca se esiste un Client con la stessa P.IVA che ha un dominio
            if (!$metaPiva->domain) {
                $clientWithDomain = \App\Models\Client::where('piva', $metaPiva->piva)
                    ->whereNotNull('domain')
                    ->first();
                
                if ($clientWithDomain) {
                    $metaPiva->update(['domain' => $clientWithDomain->domain]);
                }
            }
        });

        static::updated(function ($metaPiva) {
            // Se il MetaPiva ha un dominio e una P.IVA, aggiorna tutti i Client con la stessa P.IVA che non hanno dominio
            if ($metaPiva->domain && $metaPiva->piva && $metaPiva->wasChanged('domain')) {
                \App\Models\Client::where('piva', $metaPiva->piva)
                    ->whereNull('domain')
                    ->update(['domain' => $metaPiva->domain]);
            }
        });

        static::saving(function ($metaPiva) {
            // Pulisci il dominio prima di salvarlo
            if ($metaPiva->domain) {
                $metaPiva->domain = self::cleanDomain($metaPiva->domain);
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
}
