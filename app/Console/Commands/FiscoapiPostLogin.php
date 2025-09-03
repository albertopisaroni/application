<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FiscoapiSession;
use App\Models\User;
use App\Models\Client;
use App\Models\MetaPiva;
use App\Models\Invoice;
use App\Models\InvoiceNumbering;
use App\Models\InvoicePaymentSchedule;
use App\Models\InvoicePayment;
use App\Models\InvoicePassive;
use App\Services\FiscoApiService;
use Illuminate\Support\Facades\Http;

class FiscoapiPostLogin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fiscoapi:post-login {id_sessione} {--fetch_all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute post-login actions for FiscoApi session';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id_sessione = $this->argument('id_sessione');
        $fetch_all = $this->option('fetch_all') ?? false;
        
        $session = FiscoapiSession::where('id_sessione', $id_sessione)->first();
        $user = User::where('id', $session->user_id)->first();
        $company = $user->currentCompany;

        // Estrai le partite IVA dalla risposta della sessione
        $partiteIva = [];
        
        if ($session->response && isset($session->response['iva_servizi']['lista_utenti_lavoro'])) {
            $partiteIva = array_keys($session->response['iva_servizi']['lista_utenti_lavoro']);
        }

        \Log::info('Eseguito post-login', [
            'user' => $user->name,
            'company' => $company->name,
            'session' => $session,
            'user_id' => $user->id,
            'company_id' => $company->id,
            'fetch_all' => $fetch_all,
            'partite_iva' => $partiteIva,
            'session_response' => $session->response,
        ]);

        // Cicla tutte le partite IVA
        foreach ($partiteIva as $partitaIva) {
            \Log::info('Processando partita IVA', [
                'partita_iva' => $partitaIva,
                'fetch_all' => $fetch_all,
                'company_piva' => $company->piva,
            ]);

            // Controlla se processare questa partita IVA
            $shouldProcess = $fetch_all || $partitaIva == $company->piva;
            
            if ($shouldProcess) {
                // Controlla lo stato della partita IVA
                $stato = $session->response['iva_servizi']['lista_utenti_lavoro'][$partitaIva]['stato'] ?? null;
                
                \Log::info('Stato partita IVA', [
                    'partita_iva' => $partitaIva,
                    'stato' => $stato,
                ]);

                // Inizializza se necessario
                if ($stato !== 'inizializzato') {
                    \Log::info('Inizializzazione partita IVA richiesta', [
                        'partita_iva' => $partitaIva,
                    ]);
                    $this->inizializzaPartitaIva($session, $partitaIva);
                } else {
                    \Log::info('Partita IVA già inizializzata', [
                        'partita_iva' => $partitaIva,
                    ]);
                }

                // Recupera le fatture attive (emesse)
                \Log::info('Recupero fatture attive per partita IVA', [
                    'partita_iva' => $partitaIva,
                ]);
                $this->recuperaFatture($session, $partitaIva);
                
                // Recupera le fatture passive (ricevute)
                \Log::info('Recupero fatture passive per partita IVA', [
                    'partita_iva' => $partitaIva,
                ]);
                $this->recuperaFatturePassive($session, $partitaIva);
            } else {
                \Log::info('Partita IVA saltata', [
                    'partita_iva' => $partitaIva,
                    'reason' => 'Non corrisponde alla company e fetch_all è false',
                ]);
            }
        }

        return 0;
    }

    /**
     * Inizializza una partita IVA tramite FiscoApi
     */
    private function inizializzaPartitaIva(FiscoapiSession $session, string $partitaIva)
    {
        try {
            $fiscoApi = new FiscoApiService();
            $token = $fiscoApi->getBearerToken();
            
            if (!$token) {
                \Log::error('Impossibile ottenere token per inizializzazione', [
                    'partita_iva' => $partitaIva,
                    'session_id' => $session->id_sessione,
                ]);
                return false;
            }

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->patch(config('services.fiscoapi.base_url') . '/inizializza_utente_lavoro/' . $session->id_sessione, [
                'servizio' => 'iva_servizi',
                'utente_lavoro' => $partitaIva,
            ]);

            if ($response->successful()) {
                $sessionData = $response->json('sessione');
                
                // Aggiorna la sessione nel database con la nuova risposta
                $session->update([
                    'response' => $sessionData,
                ]);

                \Log::info('Inizializzazione partita IVA avviata', [
                    'partita_iva' => $partitaIva,
                    'session_id' => $session->id_sessione,
                    'response' => $sessionData,
                ]);

                return true;
            } else {
                \Log::error('Errore inizializzazione partita IVA', [
                    'partita_iva' => $partitaIva,
                    'session_id' => $session->id_sessione,
                    'response' => $response->body(),
                    'status' => $response->status(),
                ]);

                return false;
            }
        } catch (\Exception $e) {
            \Log::error('Eccezione durante inizializzazione partita IVA', [
                'partita_iva' => $partitaIva,
                'session_id' => $session->id_sessione,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Recupera tutte le fatture per una partita IVA
     */
    private function recuperaFatture(FiscoapiSession $session, string $partitaIva)
    {
        try {
            $fiscoApi = new FiscoApiService();
            $token = $fiscoApi->getBearerToken();
            
            if (!$token) {
                \Log::error('Impossibile ottenere token per recupero fatture', [
                    'partita_iva' => $partitaIva,
                    'session_id' => $session->id_sessione,
                ]);
                return false;
            }

            // Calcola i periodi di 3 mesi per gli ultimi 5 anni
            $fine = now();
            $inizio = $fine->copy()->subYears(1);

            // // Calcola i periodi di 3 mesi per gli ultimi 5 anni
            // $fine = now();
            // $inizio = $fine->copy()->subYears(5);
            
            $periodi = [];
            $dataCorrente = $inizio->copy();
            
            while ($dataCorrente->lt($fine)) {
                $periodoInizio = $dataCorrente->copy();
                $periodoFine = $dataCorrente->copy()->addMonths(3);
                
                // Assicurati che l'ultimo periodo non superi la data corrente
                if ($periodoFine->gt($fine)) {
                    $periodoFine = $fine->copy();
                }
                
                $periodi[] = [
                    'inizio' => $periodoInizio->timestamp * 1000,
                    'fine' => $periodoFine->timestamp * 1000,
                    'inizio_data' => $periodoInizio->format('Y-m-d'),
                    'fine_data' => $periodoFine->format('Y-m-d'),
                ];
                
                $dataCorrente = $periodoFine;
            }

            \Log::info('Periodi di ricerca calcolati', [
                'partita_iva' => $partitaIva,
                'session_id' => $session->id_sessione,
                'numero_periodi' => count($periodi),
                'periodi' => $periodi,
            ]);

            $totaleFatture = 0;

            // Cicla tutti i periodi di 3 mesi
            foreach ($periodi as $periodo) {
                $fatturePeriodo = $this->recuperaFatturePeriodo($session, $partitaIva, $token, $periodo);
                $totaleFatture += $fatturePeriodo;
                
                // Pausa breve tra le chiamate per evitare rate limiting
                if (count($periodi) > 1) {
                    sleep(4);
                }
            }

            \Log::info('Recupero fatture completato', [
                'partita_iva' => $partitaIva,
                'session_id' => $session->id_sessione,
                'totale_fatture' => $totaleFatture,
                'periodi_processati' => count($periodi),
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Eccezione durante recupero fatture', [
                'partita_iva' => $partitaIva,
                'session_id' => $session->id_sessione,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Recupera tutte le fatture passive (ricevute) per una partita IVA
     */
    private function recuperaFatturePassive(FiscoapiSession $session, string $partitaIva)
    {
        try {
            $fiscoApi = new FiscoApiService();
            $token = $fiscoApi->getBearerToken();
            
            if (!$token) {
                \Log::error('Impossibile ottenere token per recupero fatture passive', [
                    'partita_iva' => $partitaIva,
                    'session_id' => $session->id_sessione,
                ]);
                return false;
            }

            // Calcola i periodi di 3 mesi per gli ultimi 5 anni
            $fine = now();
            $inizio = $fine->copy()->subYears(5);
            
            $periodi = [];
            $dataCorrente = $inizio->copy();
            
            while ($dataCorrente->lt($fine)) {
                $periodoInizio = $dataCorrente->copy();
                $periodoFine = $dataCorrente->copy()->addMonths(3);
                
                // Assicurati che l'ultimo periodo non superi la data corrente
                if ($periodoFine->gt($fine)) {
                    $periodoFine = $fine->copy();
                }
                
                $periodi[] = [
                    'inizio' => $periodoInizio->timestamp * 1000,
                    'fine' => $periodoFine->timestamp * 1000,
                    'inizio_data' => $periodoInizio->format('Y-m-d'),
                    'fine_data' => $periodoFine->format('Y-m-d'),
                ];
                
                $dataCorrente = $periodoFine;
            }

            \Log::info('Periodi di ricerca per fatture passive calcolati', [
                'partita_iva' => $partitaIva,
                'session_id' => $session->id_sessione,
                'numero_periodi' => count($periodi),
                'periodi' => $periodi,
            ]);

            $totaleFatturePassive = 0;

            // Cicla tutti i periodi di 3 mesi
            foreach ($periodi as $periodo) {
                $fatturePeriodo = $this->recuperaFatturePassivePeriodo($session, $partitaIva, $token, $periodo);
                $totaleFatturePassive += $fatturePeriodo;
                
                // Pausa breve tra le chiamate per evitare rate limiting
                if (count($periodi) > 1) {
                    sleep(4);
                }
            }

            \Log::info('Recupero fatture passive completato', [
                'partita_iva' => $partitaIva,
                'session_id' => $session->id_sessione,
                'totale_fatture_passive' => $totaleFatturePassive,
                'periodi_processati' => count($periodi),
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Eccezione durante recupero fatture passive', [
                'partita_iva' => $partitaIva,
                'session_id' => $session->id_sessione,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Recupera fatture passive per un singolo periodo di 3 mesi
     */
    private function recuperaFatturePassivePeriodo(FiscoapiSession $session, string $partitaIva, string $token, array $periodo)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->get(config('services.fiscoapi.base_url') . '/iva_servizi/fatture_ricevute', [
                'id_sessione' => $session->id_sessione,
                'utente_lavoro' => $partitaIva,
                'inizio' => $periodo['inizio'],
                'fine' => $periodo['fine'],
            ]);

            if ($response->successful()) {
                $fatture = $response->json('fatture', []);
                
                \Log::info('Fatture passive recuperate per periodo', [
                    'partita_iva' => $partitaIva,
                    'session_id' => $session->id_sessione,
                    'periodo_inizio' => $periodo['inizio_data'],
                    'periodo_fine' => $periodo['fine_data'],
                    'numero_fatture' => count($fatture),
                    'fatture' => $fatture,
                ]);

                // Cicla tutte le fatture passive del periodo
                foreach ($fatture as $fattura) {
                    $this->processaFatturaPassiva($fattura, $session);
                }

                return count($fatture);
            } else {
                \Log::error('Errore recupero fatture passive per periodo', [
                    'partita_iva' => $partitaIva,
                    'session_id' => $session->id_sessione,
                    'periodo_inizio' => $periodo['inizio_data'],
                    'periodo_fine' => $periodo['fine_data'],
                    'response' => $response->body(),
                    'status' => $response->status(),
                ]);

                return 0;
            }
        } catch (\Exception $e) {
            \Log::error('Eccezione durante recupero fatture passive per periodo', [
                'partita_iva' => $partitaIva,
                'session_id' => $session->id_sessione,
                'periodo_inizio' => $periodo['inizio_data'],
                'periodo_fine' => $periodo['fine_data'],
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Recupera fatture per un singolo periodo di 3 mesi
     */
    private function recuperaFatturePeriodo(FiscoapiSession $session, string $partitaIva, string $token, array $periodo)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->get(config('services.fiscoapi.base_url') . '/iva_servizi/fatture_emesse', [
                'id_sessione' => $session->id_sessione,
                'utente_lavoro' => $partitaIva,
                'inizio' => $periodo['inizio'],
                'fine' => $periodo['fine'],
            ]);

            if ($response->successful()) {
                $fatture = $response->json('fatture', []);
                
                \Log::info('Fatture recuperate per periodo', [
                    'partita_iva' => $partitaIva,
                    'session_id' => $session->id_sessione,
                    'periodo_inizio' => $periodo['inizio_data'],
                    'periodo_fine' => $periodo['fine_data'],
                    'numero_fatture' => count($fatture),
                    'fatture' => $fatture,
                ]);

                // Cicla tutte le fatture del periodo
                foreach ($fatture as $fattura) {
                    $this->processaFattura($fattura, $session);
                }

                return count($fatture);
            } else {
                \Log::error('Errore recupero fatture per periodo', [
                    'partita_iva' => $partitaIva,
                    'session_id' => $session->id_sessione,
                    'periodo_inizio' => $periodo['inizio_data'],
                    'periodo_fine' => $periodo['fine_data'],
                    'response' => $response->body(),
                    'status' => $response->status(),
                ]);

                return 0;
            }
        } catch (\Exception $e) {
            \Log::error('Eccezione durante recupero fatture per periodo', [
                'partita_iva' => $partitaIva,
                'session_id' => $session->id_sessione,
                'periodo_inizio' => $periodo['inizio_data'],
                'periodo_fine' => $periodo['fine_data'],
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Processa una singola fattura e crea il cliente se necessario
     */
    private function processaFattura(array $fattura, FiscoapiSession $session)
    {
        $pivaCliente = $fattura['pivaCliente'] ?? null;
        
        if (!$pivaCliente) {
            \Log::warning('Fattura senza P.IVA cliente', [
                'id_fattura' => $fattura['idFattura'] ?? 'N/A',
                'session_id' => $session->id_sessione,
            ]);
            return;
        }

        // Rimuovi il prefisso IT se presente
        $pivaCliente = ltrim($pivaCliente, 'IT');

        // Verifica se il cliente esiste già
        $clienteEsistente = Client::where('piva', $pivaCliente)
            ->where('company_id', $session->user->currentCompany->id)
            ->first();

        if (!$clienteEsistente) {
            // Crea il cliente
            $this->creaCliente($pivaCliente, $session);
            $clienteEsistente = Client::where('piva', $pivaCliente)
                ->where('company_id', $session->user->currentCompany->id)
                ->first();
        }

        // Salva la fattura come Invoice se non esiste già
        $this->salvaFattura($fattura, $clienteEsistente, $session);
    }

    /**
     * Processa una singola fattura passiva e crea il fornitore se necessario
     */
    private function processaFatturaPassiva(array $fattura, FiscoapiSession $session)
    {
        // Per le fatture passive, la P.IVA del fornitore è in 'pivaEmittente'
        $pivaFornitore = $fattura['pivaEmittente'] ?? null;
        $cfFornitore = $fattura['cfEmittente'] ?? null;
        
        // Se non c'è P.IVA, prova con il codice fiscale
        if (!$pivaFornitore && !$cfFornitore) {
            \Log::warning('Fattura passiva senza P.IVA o CF fornitore', [
                'id_fattura' => $fattura['idFattura'] ?? 'N/A',
                'session_id' => $session->id_sessione,
                'denominazione_emittente' => $fattura['denominazioneEmittente'] ?? 'N/A',
            ]);
            return;
        }

        // Usa P.IVA se disponibile, altrimenti CF
        $identificativoFornitore = $pivaFornitore ?: $cfFornitore;
        
        // Rimuovi il prefisso IT se presente
        $identificativoFornitore = ltrim($identificativoFornitore, 'IT');

        // Verifica se il fornitore esiste già (come client)
        // Per CF, usiamo un prefisso "CF:" nel campo piva per distinguerlo
        $fornitoreEsistente = null;
        if ($pivaFornitore) {
            $fornitoreEsistente = Client::where('piva', $identificativoFornitore)
                ->where('company_id', $session->user->currentCompany->id)
                ->first();
        } else if ($cfFornitore) {
            // Cerca CF con prefisso "CF:"
            $fornitoreEsistente = Client::where('piva', 'CF:' . $identificativoFornitore)
                ->where('company_id', $session->user->currentCompany->id)
                ->first();
        }

        if (!$fornitoreEsistente) {
            // Crea il fornitore (come client)
            $this->creaFornitore($identificativoFornitore, $fattura, $session);
            
            // Riprova a trovarlo dopo la creazione
            if ($pivaFornitore) {
                $fornitoreEsistente = Client::where('piva', $identificativoFornitore)
                    ->where('company_id', $session->user->currentCompany->id)
                    ->first();
            } else {
                // Cerca CF con prefisso "CF:"
                $fornitoreEsistente = Client::where('piva', 'CF:' . $identificativoFornitore)
                    ->where('company_id', $session->user->currentCompany->id)
                    ->first();
            }
        }

        if ($fornitoreEsistente) {
            // Salva la fattura passiva come InvoicePassive se non esiste già
            $this->salvaFatturaPassiva($fattura, $fornitoreEsistente, $session);
        } else {
            \Log::error('Impossibile creare o trovare fornitore per fattura passiva', [
                'id_fattura' => $fattura['idFattura'] ?? 'N/A',
                'identificativo_fornitore' => $identificativoFornitore,
                'session_id' => $session->id_sessione,
            ]);
        }
    }

    /**
     * Crea un nuovo cliente
     */
    private function creaCliente(string $piva, FiscoapiSession $session)
    {
        try {
            $company = $session->user->currentCompany;
            
            // Cerca in MetaPiva
            $metaPiva = MetaPiva::where('piva', $piva)->first();
            
            if ($metaPiva) {
                $clienteData = [
                    'company_id' => $company->id,
                    'name' => $metaPiva->name,
                    'domain' => $metaPiva->domain,
                    'piva' => $metaPiva->piva,
                    'address' => $metaPiva->address,
                    'cap' => $metaPiva->cap,
                    'city' => $metaPiva->city,
                    'province' => $metaPiva->province,
                    'country' => $metaPiva->country,
                    'sdi' => $metaPiva->sdi,
                    'pec' => $metaPiva->pec,
                ];
            } else {
                // Cerca tramite API OpenAPI
                $resp = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.openapi.company.token'),
                ])
                ->get(env('OPENAPI_COMPANY_URL') . '/IT-start/' . $piva)
                ->throw()
                ->json('data.0');

                $address = $resp['address']['registeredOffice'] ?? [];
                $clienteData = [
                    'company_id' => $company->id,
                    'name' => $resp['companyName'] ?? '',
                    'piva' => $piva,
                    'address' => $address['streetName'] ?? '',
                    'cap' => $address['zipCode'] ?? '',
                    'city' => $address['town'] ?? '',
                    'province' => $address['province'] ?? '',
                    'country' => 'IT',
                    'sdi' => $resp['sdiCode'] ?? '',
                    'pec' => null,
                ];

                if ($clienteData['sdi'] === '0000000') {
                    $pec = Http::withHeaders([
                        'Authorization' => 'Bearer ' . config('services.openapi.company.token'),
                    ])
                    ->get(env('OPENAPI_COMPANY_URL') . '/IT-pec/' . $piva)
                    ->json('data.0.pec');
                    $clienteData['pec'] = $pec;
                }

                // Salva in MetaPiva per cache
                MetaPiva::create([
                    'name' => $clienteData['name'],
                    'piva' => $piva,
                    'address' => $clienteData['address'],
                    'cap' => $clienteData['cap'],
                    'city' => $clienteData['city'],
                    'province' => $clienteData['province'],
                    'country' => $clienteData['country'],
                    'sdi' => $clienteData['sdi'],
                    'pec' => $clienteData['pec'],
                ]);
            }

            // Crea il cliente
            $cliente = Client::create($clienteData);

            \Log::info('Cliente creato', [
                'piva' => $piva,
                'nome' => $cliente->name,
                'company_id' => $company->id,
                'cliente_id' => $cliente->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('Errore creazione cliente', [
                'piva' => $piva,
                'company_id' => $session->user->currentCompany->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Crea un nuovo fornitore (come client)
     */
    private function creaFornitore(string $identificativo, array $fattura, FiscoapiSession $session)
    {
        try {
            $company = $session->user->currentCompany;
            $pivaEmittente = $fattura['pivaEmittente'] ?? null;
            $cfEmittente = $fattura['cfEmittente'] ?? null;
            $denominazioneEmittente = $fattura['denominazioneEmittente'] ?? '';
            
            // Determina se è P.IVA o CF
            $isPiva = !empty($pivaEmittente);
            $fornitoreData = [
                'company_id' => $company->id,
                'name' => $denominazioneEmittente,
            ];

            if ($isPiva) {
                // Ha P.IVA - cerca in MetaPiva e poi API
                $metaPiva = MetaPiva::where('piva', $identificativo)->first();
                
                if ($metaPiva) {
                    $fornitoreData = array_merge($fornitoreData, [
                        'domain' => $metaPiva->domain,
                        'piva' => $metaPiva->piva,
                        'address' => $metaPiva->address,
                        'cap' => $metaPiva->cap,
                        'city' => $metaPiva->city,
                        'province' => $metaPiva->province,
                        'country' => $metaPiva->country,
                        'sdi' => $metaPiva->sdi,
                        'pec' => $metaPiva->pec,
                    ]);
                } else {
                    // Cerca tramite API OpenAPI
                    try {
                        $resp = Http::withHeaders([
                            'Authorization' => 'Bearer ' . config('services.openapi.company.token'),
                        ])
                        ->get(env('OPENAPI_COMPANY_URL') . '/IT-start/' . $identificativo)
                        ->throw()
                        ->json('data.0');

                        $address = $resp['address']['registeredOffice'] ?? [];
                        $fornitoreData = array_merge($fornitoreData, [
                            'name' => $resp['companyName'] ?? $denominazioneEmittente,
                            'piva' => $identificativo,
                            'address' => $address['streetName'] ?? '',
                            'cap' => $address['zipCode'] ?? '',
                            'city' => $address['town'] ?? '',
                            'province' => $address['province'] ?? '',
                            'country' => 'IT',
                            'sdi' => $resp['sdiCode'] ?? '',
                            'pec' => null,
                        ]);

                        if ($fornitoreData['sdi'] === '0000000') {
                            $pec = Http::withHeaders([
                                'Authorization' => 'Bearer ' . config('services.openapi.company.token'),
                            ])
                            ->get(env('OPENAPI_COMPANY_URL') . '/IT-pec/' . $identificativo)
                            ->json('data.0.pec');
                            $fornitoreData['pec'] = $pec;
                        }

                        // Salva in MetaPiva per cache
                        MetaPiva::create([
                            'name' => $fornitoreData['name'],
                            'piva' => $identificativo,
                            'address' => $fornitoreData['address'],
                            'cap' => $fornitoreData['cap'],
                            'city' => $fornitoreData['city'],
                            'province' => $fornitoreData['province'],
                            'country' => $fornitoreData['country'],
                            'sdi' => $fornitoreData['sdi'],
                            'pec' => $fornitoreData['pec'],
                        ]);
                    } catch (\Exception $apiException) {
                        \Log::warning('API OpenAPI fallita per fornitore, uso dati dalla fattura', [
                            'identificativo' => $identificativo,
                            'error' => $apiException->getMessage(),
                        ]);
                        
                        // Fallback: usa solo i dati dalla fattura
                        $fornitoreData = array_merge($fornitoreData, [
                            'piva' => $identificativo,
                            'country' => 'IT',
                        ]);
                    }
                }
            } else {
                // Ha solo CF - usa il campo piva con prefisso "CF:" per distinguerlo
                $fornitoreData = array_merge($fornitoreData, [
                    'piva' => 'CF:' . $identificativo,
                    'country' => 'IT',
                ]);
            }

            // Crea il fornitore
            $fornitore = Client::create($fornitoreData);

            \Log::info('Fornitore creato', [
                'identificativo' => $identificativo,
                'tipo' => $isPiva ? 'P.IVA' : 'CF',
                'nome' => $fornitore->name,
                'company_id' => $company->id,
                'fornitore_id' => $fornitore->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('Errore creazione fornitore', [
                'identificativo' => $identificativo,
                'company_id' => $session->user->currentCompany->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Salva una fattura passiva o nota di credito come InvoicePassive se non esiste già
     */
    private function salvaFatturaPassiva(array $fattura, Client $fornitore, FiscoapiSession $session)
    {
        try {
            $company = $session->user->currentCompany;
            
            // Verifica se la fattura passiva esiste già usando numeroFattura e dataFattura come chiave univoca
            $numeroFattura = $fattura['numeroFattura'] ?? null;
            $dataFattura = $fattura['dataFattura'] ?? null;
            $tipoDocumento = $fattura['tipoDocumento'] ?? 'Fattura';
            
            if (!$numeroFattura || !$dataFattura) {
                \Log::warning('Documento passivo senza numero o data', [
                    'id_fattura' => $fattura['idFattura'] ?? 'N/A',
                    'numero_fattura' => $numeroFattura,
                    'data_fattura' => $dataFattura,
                    'tipo_documento' => $tipoDocumento,
                    'session_id' => $session->id_sessione,
                ]);
                return;
            }

            // Determina il tipo di documento
            $documentType = ($tipoDocumento === 'Nota di credito') ? 'TD04' : 'TD01';

            // Verifica se il documento passivo esiste già
            $documentoEsistente = InvoicePassive::where('invoice_number', $numeroFattura)
                ->where('issue_date', $dataFattura)
                ->where('supplier_id', $fornitore->id)
                ->where('company_id', $company->id)
                ->where('document_type', $documentType)
                ->first();

            if ($documentoEsistente) {
                \Log::info('Documento passivo già esistente', [
                    'numero_fattura' => $numeroFattura,
                    'data_fattura' => $dataFattura,
                    'tipo_documento' => $tipoDocumento,
                    'document_type' => $documentType,
                    'fornitore_id' => $fornitore->id,
                    'company_id' => $company->id,
                ]);
                return;
            }

            // Converti gli importi da stringa a decimal
            $imponibile = $this->convertiImporto($fattura['imponibile'] ?? '0');
            $imposta = $this->convertiImporto($fattura['imposta'] ?? '0');
            $totale = $imponibile + $imposta;

            // Converti lo stato SDI
            $fileDownload = $fattura['fileDownload'] ?? [];
            $sdiStatus = $this->convertiStatoSdi($fileDownload['statoFile'] ?? '');

            // Crea la fattura passiva o nota di credito
            $invoicePassive = InvoicePassive::create([
                'company_id' => $company->id,
                'supplier_id' => $fornitore->id,
                'invoice_number' => $numeroFattura,
                'issue_date' => $dataFattura,
                'document_type' => $documentType,
                'data_accoglienza_file' => $fattura['dataAccoglienzaFile'] ?? null,
                'fiscal_year' => date('Y', strtotime($dataFattura)),
                'withholding_tax' => false,
                'inps_contribution' => false,
                'payment_method_id' => null, // Da definire in base alle esigenze
                'subtotal' => $imponibile,
                'vat' => $imposta,
                'total' => $totale,
                'global_discount' => 0,
                'header_notes' => null,
                'footer_notes' => null,
                'sdi_uuid' => null,
                'sdi_filename' => $fileDownload['idInvio'] ?? null,
                'sdi_status' => $sdiStatus,
                'sdi_error' => null,
                'sdi_error_description' => null,
                'sdi_received_at' => isset($fattura['dataConsegna']) && $fattura['dataConsegna'] ? \Carbon\Carbon::createFromFormat('d/m/Y', $fattura['dataConsegna']) : null,
                'sdi_processed_at' => null,
                'is_processed' => false,
                'is_paid' => false,
                'imported_from_callback' => true,
            ]);

            \Log::info('Documento passivo salvato', [
                'numero_documento' => $numeroFattura,
                'data_documento' => $dataFattura,
                'tipo_documento' => $tipoDocumento,
                'document_type' => $documentType,
                'fornitore_id' => $fornitore->id,
                'company_id' => $company->id,
                'invoice_passive_id' => $invoicePassive->id,
                'totale' => $totale,
            ]);

        } catch (\Exception $e) {
            \Log::error('Errore salvataggio documento passivo', [
                'numero_documento' => $fattura['numeroFattura'] ?? 'N/A',
                'data_documento' => $fattura['dataFattura'] ?? 'N/A',
                'tipo_documento' => $fattura['tipoDocumento'] ?? 'N/A',
                'fornitore_id' => $fornitore->id,
                'company_id' => $session->user->currentCompany->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Salva una fattura o nota di credito come Invoice se non esiste già
     */
    private function salvaFattura(array $fattura, Client $cliente, FiscoapiSession $session)
    {
        try {
            $company = $session->user->currentCompany;
            
            // Verifica se la fattura esiste già usando numeroFattura e dataFattura come chiave univoca
            $numeroFattura = $fattura['numeroFattura'] ?? null;
            $dataFattura = $fattura['dataFattura'] ?? null;
            $tipoDocumento = $fattura['tipoDocumento'] ?? 'Fattura';
            
            if (!$numeroFattura || !$dataFattura) {
                \Log::warning('Documento senza numero o data', [
                    'id_fattura' => $fattura['idFattura'] ?? 'N/A',
                    'numero_fattura' => $numeroFattura,
                    'data_fattura' => $dataFattura,
                    'tipo_documento' => $tipoDocumento,
                    'session_id' => $session->id_sessione,
                ]);
                return;
            }

            // Determina il tipo di documento
            $documentType = ($tipoDocumento === 'Nota di credito') ? 'TD04' : 'TD01';

            // Verifica se il documento esiste già
            $documentoEsistente = Invoice::where('invoice_number', $numeroFattura)
                ->where('issue_date', $dataFattura)
                ->where('client_id', $cliente->id)
                ->where('company_id', $company->id)
                ->where('document_type', $documentType)
                ->first();

            if ($documentoEsistente) {
                \Log::info('Documento già esistente', [
                    'numero_fattura' => $numeroFattura,
                    'data_fattura' => $dataFattura,
                    'tipo_documento' => $tipoDocumento,
                    'document_type' => $documentType,
                    'cliente_id' => $cliente->id,
                    'company_id' => $company->id,
                ]);
                return;
            }

            // Trova o crea la numerazione "Standard" per questo cliente
            $numbering = InvoiceNumbering::where('company_id', $company->id)
                ->where('type', 'standard')
                ->first();

            if (!$numbering) {
                // Crea una numerazione standard se non esiste
                $numbering = InvoiceNumbering::create([
                    'company_id' => $company->id,
                    'type' => 'standard',
                    'name' => 'Standard',
                    'current_number_invoice' => 0,
                    'last_invoice_year' => date('Y'),
                ]);
            }

            // Converti gli importi da stringa a decimal
            $imponibile = $this->convertiImporto($fattura['imponibile'] ?? '0');
            $imposta = $this->convertiImporto($fattura['imposta'] ?? '0');
            $totale = $imponibile + $imposta;

            // Converti lo stato SDI
            $fileDownload = $fattura['fileDownload'] ?? [];
            $sdiStatus = $this->convertiStatoSdi($fileDownload['statoFile'] ?? '');

            // Crea la fattura o nota di credito
            $invoice = Invoice::create([
                'company_id' => $company->id,
                'client_id' => $cliente->id,
                'numbering_id' => $numbering->id,
                'invoice_number' => $numeroFattura,
                'issue_date' => $dataFattura,
                'document_type' => $documentType,
                'data_accoglienza_file' => $fattura['dataAccoglienzaFile'] ?? null,
                'fiscal_year' => date('Y', strtotime($dataFattura)),
                'withholding_tax' => false,
                'inps_contribution' => false,
                'payment_method_id' => null, // Da definire in base alle esigenze
                'subtotal' => $imponibile,
                'vat' => $imposta,
                'total' => $totale,
                'global_discount' => 0,
                'header_notes' => null,
                'footer_notes' => null,
                'save_notes_for_future' => false,
                'sdi_uuid' => null,
                'sdi_id_invio' => $fileDownload['idInvio'] ?? null,
                'sdi_status' => $sdiStatus,
                'sdi_error' => null,
                'sdi_error_description' => null,
                'sdi_sent_at' => null,
                'sdi_received_at' => isset($fattura['dataConsegna']) && $fattura['dataConsegna'] ? \Carbon\Carbon::createFromFormat('d/m/Y', $fattura['dataConsegna']) : null,
                'sdi_attempt' => 1,
                'imported_from_ae' => true,
            ]);

            // Per le note di credito non creiamo payment schedules (sono generalmente compensate)
            if ($documentType !== 'TD04') {
                // Crea il payment schedule (100% dell'importo alla data di emissione)
                $invoice->paymentSchedules()->create([
                    'due_date' => $dataFattura,
                    'amount' => $totale,
                    'type' => 'amount',
                    'percent' => null,
                ]);

                // Crea il pagamento (100% dell'importo alla data di emissione)
                $invoice->payments()->create([
                    'amount' => $totale,
                    'payment_date' => $dataFattura,
                    'method' => 'imported_from_ae',
                    'note' => 'Pagamento importato da AE',
                ]);
            }

            // Aggiorna la numerazione se current_number era 0 (prima fattura del cliente)
            if ($numbering->current_number_invoice == 1) {
                // Trova l'ultimo documento importato per questo cliente (fatture o note di credito)
                $ultimoDocumentoImportato = Invoice::where('client_id', $cliente->id)
                    ->where('company_id', $company->id)
                    ->where('imported_from_ae', true)
                    ->where('document_type', $documentType) // Filtra per tipo di documento
                    ->orderBy('issue_date', 'desc')
                    ->orderBy('invoice_number', 'desc')
                    ->first();

                if ($ultimoDocumentoImportato) {
                    $numeroUltimoDocumento = $ultimoDocumentoImportato->invoice_number;
                    $annoUltimoDocumento = $ultimoDocumentoImportato->issue_date->year;
                    
                    // Verifica se il numero è un intero
                    if (is_numeric($numeroUltimoDocumento) && ctype_digit($numeroUltimoDocumento)) {
                        $numbering->update([
                            'current_number_invoice' => (int)$numeroUltimoDocumento,
                            'last_invoice_year' => $annoUltimoDocumento,
                        ]);
                        
                        \Log::info('Numerazione aggiornata basandosi sull\'ultimo documento importato', [
                            'cliente_id' => $cliente->id,
                            'numero_ultimo_documento' => $numeroUltimoDocumento,
                            'anno_ultimo_documento' => $annoUltimoDocumento,
                            'tipo_documento' => $tipoDocumento,
                            'document_type' => $documentType,
                            'current_number_invoice' => $numeroUltimoDocumento,
                            'last_invoice_year' => $annoUltimoDocumento,
                        ]);
                    } else {
                        // Se il numero non è un intero, usa il documento corrente
                        $year = date('Y', strtotime($dataFattura));
                        if (is_numeric($numeroFattura) && ctype_digit($numeroFattura)) {
                            $numbering->update([
                                'current_number_invoice' => (int)$numeroFattura,
                                'last_invoice_year' => $year,
                            ]);
                            
                            \Log::info('Numerazione aggiornata con documento corrente', [
                                'cliente_id' => $cliente->id,
                                'numero_documento' => $numeroFattura,
                                'anno_documento' => $year,
                                'tipo_documento' => $tipoDocumento,
                                'document_type' => $documentType,
                                'current_number_invoice' => $numeroFattura,
                                'last_invoice_year' => $year,
                            ]);
                        }
                    }
                } else {
                    // Se non ci sono documenti importati, usa il documento corrente
                    $year = date('Y', strtotime($dataFattura));
                    if (is_numeric($numeroFattura) && ctype_digit($numeroFattura)) {
                        $numbering->update([
                            'current_number_invoice' => (int)$numeroFattura,
                            'last_invoice_year' => $year,
                        ]);
                        
                        \Log::info('Numerazione aggiornata con primo documento importato', [
                            'cliente_id' => $cliente->id,
                            'numero_documento' => $numeroFattura,
                            'anno_documento' => $year,
                            'tipo_documento' => $tipoDocumento,
                            'document_type' => $documentType,
                            'current_number_invoice' => $numeroFattura,
                            'last_invoice_year' => $year,
                        ]);
                    }
                }
            }

            \Log::info('Documento salvato', [
                'numero_documento' => $numeroFattura,
                'data_documento' => $dataFattura,
                'tipo_documento' => $tipoDocumento,
                'document_type' => $documentType,
                'cliente_id' => $cliente->id,
                'company_id' => $company->id,
                'invoice_id' => $invoice->id,
                'totale' => $totale,
            ]);

        } catch (\Exception $e) {
            \Log::error('Errore salvataggio documento', [
                'numero_documento' => $fattura['numeroFattura'] ?? 'N/A',
                'data_documento' => $fattura['dataFattura'] ?? 'N/A',
                'tipo_documento' => $fattura['tipoDocumento'] ?? 'N/A',
                'cliente_id' => $cliente->id,
                'company_id' => $session->user->currentCompany->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Converte un importo da stringa a decimal
     */
    private function convertiImporto(string $importo): float
    {
        // Rimuovi il segno + e le virgole, sostituisci la virgola con il punto
        $importo = str_replace(['+', ','], ['', '.'], $importo);
        return (float) $importo;
    }

    /**
     * Converte lo stato SDI da italiano a inglese
     */
    private function convertiStatoSdi(string $stato): string
    {
        return match($stato) {
            'Consegnata' => 'delivered',
            'Inviata' => 'sent',
            'Errore' => 'error',
            'In attesa' => 'pending',
            default => 'unknown',
        };
    }
}
