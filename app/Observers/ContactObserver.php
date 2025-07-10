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