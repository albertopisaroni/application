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
    }
}
