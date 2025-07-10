<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Contact;

class PopulateClientDomains extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients:populate-domains';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Popola il campo domain dei clienti esistenti estraendolo dall\'email del contatto primario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Inizio popolamento domini per i clienti...');

        $clients = Client::whereNull('domain')->get();
        $updated = 0;
        $skipped = 0;

        foreach ($clients as $client) {
            $primaryContact = $client->primaryContact;
            
            if (!$primaryContact || !$primaryContact->email) {
                $this->warn("Cliente {$client->name} (ID: {$client->id}) - Nessun contatto primario o email mancante");
                $skipped++;
                continue;
            }

            $email = $primaryContact->email;
            
            if (!str_contains($email, '@')) {
                $this->warn("Cliente {$client->name} (ID: {$client->id}) - Email non valida: {$email}");
                $skipped++;
                continue;
            }

            $domain = strtolower(substr(strrchr($email, "@"), 1));
            
            // Verifica se è un dominio personale
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

            if (in_array($domain, $personalDomains)) {
                $this->info("Cliente {$client->name} (ID: {$client->id}) - Dominio personale: {$domain} (saltato)");
                $skipped++;
                continue;
            }

            $client->update(['domain' => $domain]);
            $this->info("Cliente {$client->name} (ID: {$client->id}) - Dominio aggiornato: {$domain}");
            $updated++;
        }

        $this->info("Operazione completata!");
        $this->info("Clienti aggiornati: {$updated}");
        $this->info("Clienti saltati: {$skipped}");

        return Command::SUCCESS;
    }
}
