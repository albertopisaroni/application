<?php

namespace App\Observers;

use App\Models\Contact;
use App\Models\MetaDomain;

class ContactObserver
{
    public function saved(Contact $contact): void
    {
        // Se l'email è valida, estrai il dominio
        if (!$contact->email || !str_contains($contact->email, '@')) {
            return;
        }

        $domain = strtolower(substr(strrchr($contact->email, "@"), 1));

        // Escludi email personali tipo @gmail.com, ecc.
        $excludedDomains = [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'live.com', 'aol.com', 'outlook.it',
            'outlook.com', 'icloud.com', 'mail.com', 'msn.com', 'protonmail.com',
            'tiscali.it', 'libero.it', 'virgilio.it', 'fastwebnet.it', 'tim.it', 'tin.it',
            'email.it', 'inwind.it', 'vodafone.it', 'poste.it'
        ];

        if (in_array($domain, $excludedDomains)) {
            return;
        }

        // Se non esiste ancora il dominio nel sistema, crealo e genera il logo
        $alreadyExists = \App\Models\MetaDomain::where('domain', $domain)->exists();

        if (!$alreadyExists) {
            MetaDomain::findOrCreateByDomain($domain);
        }
    }
}