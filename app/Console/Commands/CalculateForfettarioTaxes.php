<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\Tax;
use App\Models\Invoice;
use App\Services\TaxCalculationService;
use App\Mail\TaxCalculationCompleted;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CalculateForfettarioTaxes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taxes:calculate-forfettario 
                            {--year= : Anno di calcolo (default: anno corrente)}
                            {--company= : ID della company specifica da elaborare}
                            {--batch-size=50 : Numero di companies da processare per batch}
                            {--dry-run : Esegui in modalità simulazione senza salvare}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcola le tasse per le aziende in regime forfettario (RF19)';

    protected TaxCalculationService $taxService;

    public function __construct(TaxCalculationService $taxService)
    {
        parent::__construct();
        $this->taxService = $taxService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->option('year') ?: Carbon::now()->year;
        $companyId = $this->option('company');
        $batchSize = $this->option('batch-size');
        $dryRun = $this->option('dry-run');

        $this->info("=== CALCOLO TASSE REGIME FORFETTARIO ===");
        $this->info("Anno di calcolo: {$year}");
        
        if ($dryRun) {
            $this->warn("MODALITÀ SIMULAZIONE ATTIVA - Nessun dato verrà salvato");
        }

        // Se specificata una company, elabora solo quella
        if ($companyId) {
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("Company con ID {$companyId} non trovata");
                return 1;
            }
            
            // Calcola il fatturato dell'anno precedente
            $totalRevenue = $this->calculateRevenueForYear($company, $year - 1);
            
            if ($totalRevenue <= 0) {
                $this->error("Company {$company->name} non ha fatturato nell'anno " . ($year - 1));
                return 1;
            }
            
            $this->info("Company {$company->name} - Fatturato " . ($year - 1) . ": € " . number_format($totalRevenue, 2));
            
            // Aggiorna temporaneamente il total_revenue per il calcolo
            $company->total_revenue = $totalRevenue;
            
            $this->processCompany($company, $year, $dryRun);
            return 0;
        }

        // Altrimenti elabora tutte le companies in regime forfettario
        $companies = Company::where('regime_fiscale', 'RF19')
            ->chunk($batchSize, function ($companies) use ($year, $dryRun) {
                foreach ($companies as $company) {
                    try {
                        // Calcola sempre il fatturato dalle fatture
                        $totalRevenue = $this->calculateRevenueForYear($company, $year - 1);
                        
                        if ($totalRevenue <= 0) {
                            $this->warn("Company {$company->name} non ha fatturato nell'anno " . ($year - 1) . ", skip");
                            continue;
                        }
                        
                        $this->info("Company {$company->name} - Fatturato " . ($year - 1) . ": € " . number_format($totalRevenue, 2));
                        
                        // Imposta il total_revenue per il calcolo (non salvato nel DB)
                        $company->total_revenue = $totalRevenue;
                        
                        $this->processCompany($company, $year, $dryRun);
                    } catch (\Exception $e) {
                        $this->error("Errore elaborazione company {$company->name}: " . $e->getMessage());
                        Log::error("Errore calcolo tasse", [
                            'company_id' => $company->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
            });

        $this->info("\nElaborazione completata!");
        return 0;
    }

    /**
     * Processa una singola company
     */
    protected function processCompany(Company $company, int $year, bool $dryRun): void
    {
        $this->info("\n--- Elaborazione: {$company->name} ---");

        // Verifica se è la prima elaborazione
        if (!$company->hasPreviousTaxRecords()) {
            $this->handleFirstTimeSetup($company, $year);
        }

        if ($dryRun) {
            $this->info("Simulazione calcolo per {$company->name}");
            // Esegui il calcolo ma non salvare
            $taxRecords = $this->taxService->calculateForCompany($company, $year);
            $this->displayTaxSummary($taxRecords);
            return;
        }

        // Calcola le tasse
        $taxRecords = $this->taxService->calculateForCompany($company, $year);

        // Mostra riepilogo
        $this->displayTaxSummary($taxRecords);

        // Invia email di notifica se configurato
        if ($company->email && config('app.send_tax_notifications', true)) {
            try {
                Mail::to($company->email)->send(new TaxCalculationCompleted($company, $taxRecords));
                $this->info("Email di notifica inviata a {$company->email}");
            } catch (\Exception $e) {
                $this->warn("Impossibile inviare email: " . $e->getMessage());
            }
        }
    }

    /**
     * Gestisce la configurazione per la prima volta
     */
    protected function handleFirstTimeSetup(Company $company, int $year): void
    {
        $this->info("Prima elaborazione per {$company->name}");
        $this->info("Calcolo automatico dati storici...");
        
        // Calcola automaticamente basandosi sulle fatture precedenti
        $this->calculateHistoricalTaxData($company, $year);
    }
    
    /**
     * Mostra informazioni sui dati storici per il calcolo dei saldi
     */
    protected function calculateHistoricalTaxData(Company $company, int $year): void
    {
        // Calcola il fatturato dell'anno precedente all'anno precedente (year - 2)
        // per informare sui calcoli che verranno fatti
        $revenueYearBefore = $this->calculateRevenueForYear($company, $year - 2);
        
        if ($revenueYearBefore > 0) {
            // Mostra stime per informazione, ma NON crea record per acconti scaduti
            $coefficiente = $company->coefficiente ?: 78.00;
            $redditoImponibile = $revenueYearBefore * ($coefficiente / 100);
            $aliquota = $company->startup ? 0.05 : 0.15;
            $impostaTotale = $redditoImponibile * $aliquota;
            $accontoTotale = $impostaTotale * 0.99;
            
            $primoAccontoStimato = round($accontoTotale * 0.40, 2);
            $secondoAccontoStimato = round($accontoTotale * 0.60, 2);
            
            // Solo informazione - NON crea record per acconti già scaduti
            if ($primoAccontoStimato > 10 || $secondoAccontoStimato > 10) {
                $this->info("Stima acconti " . ($year - 1) . " basata su fatturato " . ($year - 2) . ":");
                $this->info("  - Primo acconto stimato: € " . number_format($primoAccontoStimato, 2));
                $this->info("  - Secondo acconto stimato: € " . number_format($secondoAccontoStimato, 2));
                $this->warn("⚠️  NOTA: Se questi acconti non sono stati versati nel " . ($year - 1) . ",");
                $this->warn("   potrebbero essere dovute sanzioni. Usa il ravvedimento operoso se necessario.");
                $this->info("   Il sistema calcolerà automaticamente i saldi basandosi sui versamenti effettivi.");
            }
        } else {
            $this->info("Nessun fatturato nell'anno " . ($year - 2) . " - Prima attività per questa azienda");
        }
    }


    /**
     * Mostra il riepilogo delle tasse calcolate
     */
    protected function displayTaxSummary(array $taxRecords): void
    {
        if (empty($taxRecords)) {
            $this->warn("Nessun bollettino generato");
            return;
        }

        $this->info("\n=== RIEPILOGO BOLLETTINI GENERATI ===");
        
        // Ordina per scadenza
        usort($taxRecords, function($a, $b) {
            return $a['due_date']->timestamp <=> $b['due_date']->timestamp;
        });
        
        $headers = ['Tipo', 'Descrizione', 'Codice', 'Importo', 'Scadenza', 'Stato'];
        $rows = [];

        foreach ($taxRecords as $record) {
            $rows[] = [
                $record['tax_type'],
                $record['description'],
                $record['tax_code'] ?? '-',
                '€ ' . number_format($record['amount'], 2, ',', '.'),
                $record['due_date']->format('d/m/Y'),
                $record['payment_status'] ?? 'PENDING'
            ];
        }

        $this->table($headers, $rows);

        // Calcola totali
        $totaleImposta = collect($taxRecords)
            ->filter(fn($r) => str_contains($r['tax_type'], 'IMPOSTA_SOSTITUTIVA'))
            ->where('payment_status', '!=', Tax::STATUS_CREDIT)
            ->sum('amount');

        $totaleInps = collect($taxRecords)
            ->filter(fn($r) => str_contains($r['tax_type'], 'INPS'))
            ->where('payment_status', '!=', Tax::STATUS_CREDIT)
            ->sum('amount');

        $crediti = collect($taxRecords)
            ->where('payment_status', Tax::STATUS_CREDIT)
            ->sum('amount');

        $this->info("\n--- TOTALI ---");
        $this->info("Imposta sostitutiva: € " . number_format($totaleImposta, 2, ',', '.'));
        $this->info("Contributi INPS: € " . number_format($totaleInps, 2, ',', '.'));
        $this->info("TOTALE DA VERSARE: € " . number_format($totaleImposta + $totaleInps, 2, ',', '.'));
        
        if ($crediti > 0) {
            $this->info("Crediti generati: € " . number_format($crediti, 2, ',', '.'));
        }
        
        $this->warn("\n⚠️  NOTA: Tutti i bollettini sono stati creati come PENDING");
        $this->warn("   Vai nella sezione Tasse per gestire i pagamenti e scaricare gli F24.");
    }

    /**
     * Calcola il fatturato totale di una company per un anno specifico
     * Somma tutte le fatture ed esclude le note di credito
     */
    protected function calculateRevenueForYear(Company $company, int $year): float
    {
        // Fatture emesse nell'anno (escluse note di credito)
        $fattureEmesse = Invoice::where('company_id', $company->id)
            ->whereYear('issue_date', $year)
            ->where('document_type', '!=', 'TD04') // Escludi note di credito
            ->sum('total');

        // Note di credito emesse nell'anno
        $noteCredito = Invoice::where('company_id', $company->id)
            ->whereYear('issue_date', $year)
            ->where('document_type', 'TD04')
            ->sum('total');

        // Il fatturato netto è: fatture - note di credito
        $totalRevenue = $fattureEmesse - $noteCredito;

        $this->info("  - Fatture emesse {$year}: € " . number_format($fattureEmesse, 2));
        $this->info("  - Note di credito {$year}: € " . number_format($noteCredito, 2));
        $this->info("  - Fatturato netto {$year}: € " . number_format($totalRevenue, 2));

        return max(0, $totalRevenue); // Non può essere negativo
    }
}