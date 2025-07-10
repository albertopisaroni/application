<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FiscoapiSession;
use App\Models\User;
use App\Models\Client;
use App\Models\MetaPiva;
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

            if($fetch_all){

            }

            else{

                if($partitaIva == $company->piva){

                    $stato = $session->response['iva_servizi']['lista_utenti_lavoro'][$partitaIva]['stato'] ?? null;

                    if ($stato !== 'inizializzato') {
                        $this->inizializzaPartitaIva($session, $partitaIva);  
                    } 

                    // qui recuperiamo tutte le fatture vecchie 
                    $this->recuperaFatture($session, $partitaIva);

                }

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

            // Calcola i periodi di 3 mesi per gli ultimi 3 anni
            $fine = now();
            $inizio = $fine->copy()->subYears(3);
            
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

        if ($clienteEsistente) {
            \Log::info('Cliente già esistente', [
                'piva' => $pivaCliente,
                'nome' => $clienteEsistente->name,
                'company_id' => $session->user->currentCompany->id,
            ]);
            return;
        }

        // Crea il cliente
        $this->creaCliente($pivaCliente, $session);
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
                    'domain' => null, // Il dominio verrà estratto successivamente se disponibile
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
}
