<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Tax;
use App\Models\InpsParameter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TaxCalculationService
{
    // NOTA: Tutti i parametri INPS sono ora caricati dinamicamente dal database
    // Le costanti seguenti sono mantenute solo come fallback di emergenza
    
    const RIDUZIONE_AGEVOLAZIONE_INPS = 0.65; // Riduzione del 35%
    const RIDUZIONE_NUOVE_ATTIVITA_INPS = 0.50; // Riduzione del 50% per nuove attività (primi 36 mesi)

    protected Company $company;
    protected int $year;
    protected array $taxRecords = [];

    public function __construct()
    {
    }

    /**
     * Calcola le tasse per una company in regime forfettario
     */
    public function calculateForCompany(Company $company, int $year): array
    {
        return $this->calculateForCompanyIntelligent($company, $year);
    }

    /**
     * Calcola le tasse per una company in regime forfettario (metodo intelligente)
     */
    public function calculateForCompanyIntelligent(Company $company, int $year): array
    {
        $this->company = $company;
        $this->year = $year;
        $this->taxRecords = [];

        // Verifica che la company sia in regime forfettario
        if (!$company->isRegimeForfettario()) {
            throw new \Exception("La company {$company->name} non è in regime forfettario RF19");
        }

        // Calcola il fatturato dinamicamente dalle fatture
        $totalRevenue = $this->calculateRevenueForYear($company, $year - 1);
        
        // Verifica che ci sia un fatturato
        if ($totalRevenue <= 0) {
            Log::warning("Company {$company->name} ha fatturato zero o negativo, skip calcolo tasse");
            return [];
        }
        
        // Imposta il total_revenue per il calcolo (non salvato nel DB)
        $company->total_revenue = $totalRevenue;

        Log::info("🚀 Inizio calcolo tasse intelligente per company {$company->name}, anno {$year}");

        DB::beginTransaction();
        
        try {
            // Recupera lo storico
            $storico = $this->recuperaStorico();
            
            // Calcola prima i contributi INPS (per poterli dedurre)
            $contributiInps = $this->calcolaContributiInps($storico);
            
            // Calcola imposta sostitutiva (deducendo i contributi INPS)
            $this->calcolaImpostaSostitutiva($storico, $contributiInps);
            
            // Calcola diritto annuale CCIAA (se iscritto)
            $this->calcolaDirittoAnnualeCCIAA();
            
            // Cancella solo i record pending automatici (NON quelli manuali)
            $this->cancellaRecordPending();
            
            // Salva i nuovi record, evitando duplicati con quelli manuali
            $this->salvaRecordTasse();
            
            DB::commit();
            
            // Log dell'operazione
            $this->logOperazione();
            
            Log::info("✅ Calcolo tasse intelligente completato per company {$company->name}", [
                'records_creati' => count($this->taxRecords)
            ]);
            
            return $this->taxRecords;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Errore calcolo tasse per company {$company->id}: " . $e->getMessage());
            throw $e;
        }
    }



    /**
     * Recupera i parametri INPS per un anno specifico con fallback alle costanti
     */
    protected function getInpsParams(int $year): InpsParameter
    {
        
        $params = InpsParameter::getForYearOrLatest($year);
        
        if (!$params) {
            // Fallback: crea un oggetto con le costanti attuali
            $params = new InpsParameter();
            $params->year = $year;
            $params->minimale_commercianti_artigiani = self::MINIMALE_COMMERCIANTI_ARTIGIANI;
            $params->aliquota_commercianti = self::ALIQUOTA_COMMERCIANTI;
            $params->aliquota_commercianti_ridotta = self::ALIQUOTA_COMMERCIANTI_RIDOTTA;
            $params->aliquota_artigiani = self::ALIQUOTA_ARTIGIANI;
            $params->aliquota_artigiani_ridotta = self::ALIQUOTA_ARTIGIANI_RIDOTTA;
            $params->aliquota_gestione_separata = self::ALIQUOTA_GESTIONE_SEPARATA;
            $params->aliquota_gestione_separata_ridotta = self::ALIQUOTA_GESTIONE_SEPARATA_RIDOTTA;
            $params->contributo_fisso_commercianti = self::CONTRIBUTO_FISSO_COMMERCIANTI;
            $params->contributo_fisso_commercianti_ridotto = self::CONTRIBUTO_FISSO_COMMERCIANTI_RIDOTTO;
            $params->contributo_fisso_artigiani = self::CONTRIBUTO_FISSO_ARTIGIANI;
            $params->contributo_fisso_artigiani_ridotto = self::CONTRIBUTO_FISSO_ARTIGIANI_RIDOTTO;
            $params->contributo_maternita_annuo = self::CONTRIBUTO_MATERNITA_ANNUO;
            $params->massimale_commercianti_artigiani = self::MASSIMALE_COMMERCIANTI_ARTIGIANI;
            $params->massimale_gestione_separata = self::MASSIMALE_GESTIONE_SEPARATA;
            $params->diritto_annuale_cciaa = self::DIRITTO_ANNUALE_CCIAA;
        }
        
        return $params;
    }

    /**
     * Recupera lo storico di acconti e crediti
     */
    protected function recuperaStorico(): array
    {
        // Acconti imposta sostitutiva versati anno precedente
        $accontiImpostaVersati = $this->company->taxes()
            ->where('tax_year', $this->year - 1)
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO,
                Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO
            ])
            ->where('payment_status', Tax::STATUS_PAID)
            ->sum('amount');

        // Acconti imposta sostitutiva NON pagati (pending) anno precedente
        $accontiImpostaNonPagati = $this->company->taxes()
            ->where('tax_year', $this->year - 1)
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO,
                Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO
            ])
            ->where('payment_status', Tax::STATUS_PENDING)
            ->sum('amount');

        // Saldo imposta sostitutiva NON pagato anno precedente
        $saldoImpostaNonPagato = $this->company->taxes()
            ->where('tax_year', $this->year - 2) // L'anno prima del precedente
            ->where('tax_type', Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SALDO)
            ->where('payment_status', Tax::STATUS_PENDING)
            ->sum('amount');

        // Acconti INPS versati anno precedente
        $accontiInpsVersati = $this->company->taxes()
            ->where('tax_year', $this->year - 1)
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_INPS_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_SECONDO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_SECONDO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_TERZO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_QUARTO_ACCONTO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_SECONDO_ACCONTO
            ])
            ->where('payment_status', Tax::STATUS_PAID)
            ->sum('amount');

        // Saldi INPS versati anno precedente (necessario per calcolo saldo imposta sostitutiva)
        $saldiInpsVersati = $this->company->taxes()
            ->where('tax_year', $this->year - 1)
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_INPS_SALDO,
                Tax::TAX_TYPE_INPS_FISSI_SALDO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_SALDO
            ])
            ->where('payment_status', Tax::STATUS_PAID)
            ->sum('amount');

        // Acconti INPS NON pagati anno precedente
        $accontiInpsNonPagati = $this->company->taxes()
            ->where('tax_year', $this->year - 1)
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_INPS_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_SECONDO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_SECONDO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_TERZO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_QUARTO_ACCONTO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_SECONDO_ACCONTO
            ])
            ->where('payment_status', Tax::STATUS_PENDING)
            ->sum('amount');

        // Saldi INPS NON pagati anno precedente
        $saldiInpsNonPagati = $this->company->taxes()
            ->where('tax_year', $this->year - 2) // L'anno prima del precedente
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_INPS_SALDO,
                Tax::TAX_TYPE_INPS_FISSI_SALDO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_SALDO
            ])
            ->where('payment_status', Tax::STATUS_PENDING)
            ->sum('amount');

        // Crediti imposta sostitutiva
        $creditoImposta = $this->company->taxes()
            ->where('tax_type', Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_CREDITO)
            ->where('payment_status', Tax::STATUS_CREDIT)
            ->sum('amount');

        // Crediti INPS
        $creditoInps = $this->company->taxes()
            ->where('tax_type', Tax::TAX_TYPE_INPS_CREDITO)
            ->where('payment_status', Tax::STATUS_CREDIT)
            ->sum('amount');

        return [
            'acconti_imposta_versati' => $accontiImpostaVersati,
            'acconti_imposta_non_pagati' => $accontiImpostaNonPagati,
            'saldo_imposta_non_pagato' => $saldoImpostaNonPagato,
            'acconti_inps_versati' => $accontiInpsVersati,
            'saldi_inps_versati' => $saldiInpsVersati,
            'acconti_inps_non_pagati' => $accontiInpsNonPagati,
            'saldi_inps_non_pagati' => $saldiInpsNonPagati,
            'credito_imposta' => $creditoImposta,
            'credito_inps' => $creditoInps
        ];
    }

    /**
     * Calcola l'imposta sostitutiva con la logica corretta di saldo, sanzioni e interessi
     */
    protected function calcolaImpostaSostitutiva(array $storico, float $contributiInpsAnnoCorrente): void
    {
        // 1. Calcola reddito lordo (fatturato × coefficiente)
        $coefficiente = $this->company->coefficiente ?: 78.00;
        $redditoLordo = $this->company->total_revenue * ($coefficiente / 100);

        // 2. Calcola i contributi INPS effettivamente versati per l'anno precedente (criterio di cassa)
        // NOTA: Nel regime forfettario si deducono solo i contributi EFFETTIVAMENTE PAGATI
        $contributiInpsVersatiAnnoPrecedente = $this->calcolaContributiInpsVersatiPerAnno($this->year - 1);
        
        // 3. Calcola reddito imponibile = reddito lordo - contributi INPS effettivamente versati
        $redditoImponibile = $redditoLordo - $contributiInpsVersatiAnnoPrecedente;

        // 4. Determina aliquota
        $aliquota = $this->company->startup ? 0.05 : 0.15;

        // 5. Calcola imposta totale anno precedente (su reddito imponibile)
        $impostaTotaleAnnoPrecedente = $redditoImponibile * $aliquota;

        // 6. Calcola acconti teorici dell'anno precedente (100% dell'imposta)
        $accontoTotale = $impostaTotaleAnnoPrecedente * 1.00; // 100% non 99%
        $primoAccontoTeorico = $accontoTotale * 0.40;
        $secondoAccontoTeorico = $accontoTotale * 0.60;

        // 7. Recupera lo stato dei pagamenti degli acconti dell'anno precedente
        $accontiStatus = $this->getAccontiImpostaStatus($this->year - 1, $primoAccontoTeorico, $secondoAccontoTeorico);

        // 8. Calcola saldo (1792) = imposta_totale - acconti_EFFETTIVAMENTE_PAGATI
        // NOTA: I contributi INPS sono già stati dedotti dalla base imponibile, non dal saldo
        $accontiEffettivamentePagati = $accontiStatus['primo_pagato'] + $accontiStatus['secondo_pagato'];
        $saldo = $impostaTotaleAnnoPrecedente - $accontiEffettivamentePagati - $storico['credito_imposta'];

        // Log per debug del calcolo saldo
        Log::info("Debug Saldo Imposta Sostitutiva - CRITERIO CASSA", [
            'company_id' => $this->company->id,
            'year' => $this->year - 1,
            'reddito_lordo' => $redditoLordo,
            'contributi_inps_versati_anno_precedente' => $contributiInpsVersatiAnnoPrecedente,
            'reddito_imponibile' => $redditoImponibile,
            'aliquota' => $aliquota,
            'imposta_totale_anno_precedente' => $impostaTotaleAnnoPrecedente,
            'acconti_effettivamente_pagati' => $accontiEffettivamentePagati,
            'credito_imposta' => $storico['credito_imposta'],
            'saldo_calcolato' => $saldo
        ]);

        // 9. Genera sempre il saldo (1792)
        if ($saldo > 0) {
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SALDO,
                'tax_year' => $this->year - 1,
                'payment_year' => $this->year,
                'amount' => $saldo,
                'description' => "Saldo imposta sostitutiva " . ($this->year - 1),
                'tax_code' => Tax::TAX_CODE_IMPOSTA_SOSTITUTIVA_SALDO, // 1792
                'due_date' => Carbon::create($this->year, 6, 30)
            ]);
        } elseif ($saldo < 0) {
            // Genera credito
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_CREDITO,
                'tax_year' => $this->year - 1,
                'payment_year' => $this->year,
                'amount' => abs($saldo),
                'payment_status' => Tax::STATUS_CREDIT,
                'description' => 'Credito imposta sostitutiva da riportare',
                'due_date' => Carbon::create($this->year, 6, 30)
            ]);
        }

        // 10. NOTA: Sanzioni e interessi NON vengono generate automaticamente
        // Secondo la prassi dell'Agenzia delle Entrate, se paghi tutto con saldo 1792
        // entro la scadenza (30 giugno), sei regolare senza sanzioni automatiche.
        // Le sanzioni 8944/1944 si applicano solo in caso di ravvedimento operoso
        // durante l'anno (funzionalità separata, non implementata automaticamente).
        
        // $this->generaBolletiniAccontiNonPagati($accontiStatus, $this->year - 1); // RIMOSSO

        // 11. Calcola acconti anno corrente basati sull'imposta 2025
        // L'imposta 2025 è calcolata sul fatturato 2024 deducendo i contributi INPS versati per il 2024
        $contributiInpsVersati2024 = $this->calcolaContributiInpsVersatiPerAnno($this->year - 1);
        $redditoImponibile2025 = $redditoLordo - $contributiInpsVersati2024;
        $impostaTotale2025 = $redditoImponibile2025 * $aliquota;
        
        $accontoTotaleCorrente = $impostaTotale2025 * 1.00; // 100% dell'imposta
        $primoAccontoCorrente = $accontoTotaleCorrente * 0.40;
        $secondoAccontoCorrente = $accontoTotaleCorrente * 0.60;

        if ($primoAccontoCorrente > 0) {
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO,
                'tax_year' => $this->year,
                'payment_year' => $this->year,
                'amount' => $primoAccontoCorrente,
                'description' => "Primo acconto imposta sostitutiva " . $this->year,
                'tax_code' => Tax::TAX_CODE_IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO, // 1790
                'due_date' => Carbon::create($this->year, 6, 30)
            ]);
        }

        if ($secondoAccontoCorrente > 0) {
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO,
                'tax_year' => $this->year,
                'payment_year' => $this->year,
                'amount' => $secondoAccontoCorrente,
                'description' => "Secondo acconto imposta sostitutiva " . $this->year,
                'tax_code' => Tax::TAX_CODE_IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO, // 1791
                'due_date' => Carbon::create($this->year, 11, 30)
            ]);
        }
    }

    /**
     * Calcola il diritto annuale di iscrizione CCIAA
     */
    protected function calcolaDirittoAnnualeCCIAA(): void
    {
        // Verifica se la company è iscritta alla CCIAA (ha rea_ufficio valorizzato)
        if (empty($this->company->rea_ufficio)) {
            return; // Non iscritto alla CCIAA, skip
        }

        // Recupera i parametri per l'anno corrente
        $params = $this->getInpsParams($this->year);

        // Il diritto annuale CCIAA è dovuto per l'anno corrente con scadenza 20 agosto
        $this->aggiungiRecord([
            'tax_type' => Tax::TAX_TYPE_DIRITTO_ANNUALE_CCIAA,
            'tax_year' => $this->year,
            'payment_year' => $this->year,
            'amount' => $params->diritto_annuale_cciaa,
            'description' => "Diritto annuale iscrizione CCIAA " . $this->year . " (REA: " . $this->company->rea_ufficio . ")",
            'tax_code' => Tax::TAX_CODE_DIRITTO_ANNUALE_CCIAA, // 3850
            'due_date' => Carbon::create($this->year, 8, 20) // 20 agosto (non rateizzabile)
        ]);
    }

    /**
     * Genera ravvedimento operoso per acconti imposta sostitutiva non pagati
     * 
     * Metodo pubblico per permettere il ravvedimento operoso esplicito.
     * Utilizzare solo se il contribuente vuole regolarizzarsi durante l'anno
     * invece di aspettare il saldo 1792.
     * 
     * @param int $year Anno di riferimento per il ravvedimento
     * @return void
     */
    public function generaRavvedimentoAccontiImposta(int $year): void
    {
        // Calcola gli acconti teorici per l'anno richiesto
        $coefficiente = $this->company->coefficiente ?: 78.00;
        $redditoLordo = $this->company->total_revenue * ($coefficiente / 100);
        $contributiInpsPagatiAnnoPrecedente = $this->calcolaContributiInpsPagatiAnno($year - 1);
        $redditoImponibile = $redditoLordo - $contributiInpsPagatiAnnoPrecedente;
        $aliquota = $this->company->startup ? 0.05 : 0.15;
        $impostaTotale = $redditoImponibile * $aliquota;
        
        $accontoTotale = $impostaTotale * 1.00; // 100% dell'imposta
        $primoAccontoTeorico = $accontoTotale * 0.40;
        $secondoAccontoTeorico = $accontoTotale * 0.60;
        
        // Recupera lo stato attuale dei pagamenti
        $accontiStatus = $this->getAccontiImpostaStatus($year, $primoAccontoTeorico, $secondoAccontoTeorico);
        
        // Genera il ravvedimento
        $this->generaRavvedimentoOperoso($accontiStatus, $year);
    }

    /**
     * Recupera lo stato dei pagamenti degli acconti imposta sostitutiva
     */
    protected function getAccontiImpostaStatus(int $year, float $primoAccontoTeorico, float $secondoAccontoTeorico): array
    {
        // Controlla primo acconto
        $primoAcconto = $this->company->taxes()
            ->where('tax_year', $year)
            ->where('tax_type', Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO)
            ->first();

        // Controlla secondo acconto
        $secondoAcconto = $this->company->taxes()
            ->where('tax_year', $year)
            ->where('tax_type', Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO)
            ->first();

        return [
            'primo_status' => $primoAcconto ? $primoAcconto->payment_status : 'MISSING',
            'primo_importo' => $primoAccontoTeorico, // Usa sempre l'importo teorico corretto
            'primo_pagato' => ($primoAcconto && $primoAcconto->payment_status === Tax::STATUS_PAID) ? $primoAcconto->amount : 0,
            'primo_due_date' => Carbon::create($year, 6, 30),
            
            'secondo_status' => $secondoAcconto ? $secondoAcconto->payment_status : 'MISSING',
            'secondo_importo' => $secondoAccontoTeorico, // Usa sempre l'importo teorico corretto
            'secondo_pagato' => ($secondoAcconto && $secondoAcconto->payment_status === Tax::STATUS_PAID) ? $secondoAcconto->amount : 0,
            'secondo_due_date' => Carbon::create($year, 11, 30),
        ];
    }

    /**
     * Genera bollettini per ravvedimento operoso degli acconti non pagati
     * 
     * NOTA: Questo metodo NON viene chiamato automaticamente.
     * È disponibile solo per casi specifici di ravvedimento durante l'anno.
     * 
     * Secondo la prassi dell'Agenzia delle Entrate:
     * - Se paghi tutto con saldo 1792 entro giugno: NESSUNA sanzione
     * - Se vuoi ravvederti durante l'anno: usa questo metodo
     * 
     * @param array $accontiStatus Status degli acconti
     * @param int $year Anno di riferimento
     */
    protected function generaRavvedimentoOperoso(array $accontiStatus, int $year): void
    {
        $sanzioniTotali = 0;
        $interessiTotali = 0;
        $dataOggi = Carbon::now();

        // Primo acconto non pagato - SOLO sanzioni e interessi (NO principal)
        if ($accontiStatus['primo_status'] !== Tax::STATUS_PAID && $accontiStatus['primo_importo'] > 0) {
            $importo = $accontiStatus['primo_importo'];
            $scadenza = $accontiStatus['primo_due_date'];
            $giorni = max(0, $scadenza->diffInDays($dataOggi));

            // NON generiamo il principal 1790 perché è coperto dal saldo 1792
            // Calcoliamo solo sanzioni e interessi per il ravvedimento
            $sanzioni = $this->calcolaSanzioni($importo, $giorni);
            $interessi = $this->calcolaInteressi($importo, $scadenza, $dataOggi);
            $sanzioniTotali += $sanzioni;
            $interessiTotali += $interessi;
        }

        // Secondo acconto non pagato - SOLO sanzioni e interessi (NO principal)
        if ($accontiStatus['secondo_status'] !== Tax::STATUS_PAID && $accontiStatus['secondo_importo'] > 0) {
            $importo = $accontiStatus['secondo_importo'];
            $scadenza = $accontiStatus['secondo_due_date'];
            $giorni = max(0, $scadenza->diffInDays($dataOggi));

            // NON generiamo il principal 1791 perché è coperto dal saldo 1792
            // Calcoliamo solo sanzioni e interessi per il ravvedimento
            $sanzioni = $this->calcolaSanzioni($importo, $giorni);
            $interessi = $this->calcolaInteressi($importo, $scadenza, $dataOggi);
            $sanzioniTotali += $sanzioni;
            $interessiTotali += $interessi;
        }

        // Genera bollettini sanzioni totali
        if ($sanzioniTotali > 0) {
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_SANZIONI,
                'tax_year' => $year,
                'payment_year' => $this->year,
                'amount' => $sanzioniTotali,
                'description' => "Sanzioni per ravvedimento acconti imposta sostitutiva {$year}",
                'tax_code' => Tax::TAX_CODE_SANZIONI, // 8944
                'due_date' => Carbon::create($this->year, 6, 30)
            ]);
        }

        // Genera bollettini interessi totali
        if ($interessiTotali > 0) {
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_INTERESSI,
                'tax_year' => $year,
                'payment_year' => $this->year,
                'amount' => $interessiTotali,
                'description' => "Interessi moratori acconti imposta sostitutiva {$year}",
                'tax_code' => Tax::TAX_CODE_INTERESSI, // 1944
                'due_date' => Carbon::create($this->year, 6, 30)
            ]);
        }
    }

    /**
     * Calcola sanzioni per ravvedimento operoso (Codice 8944)
     * Base normativa: art. 13 D.Lgs. 472/1997
     * Base sanzione per omesso versamento: 25% (dal 2024, era 30% prima)
     */
    protected function calcolaSanzioni(float $importo, int $giorni): float
    {
        // Tabella ufficiale ravvedimento operoso 2024+
        if ($giorni <= 14) {
            return $importo * 0.001 * $giorni; // 0,1% per giorno (max 1,4%)
        } elseif ($giorni <= 30) {
            return $importo * 0.015; // 1,5%
        } elseif ($giorni <= 90) {
            return $importo * 0.0167; // 1,67%
        } elseif ($giorni <= 365) {
            return $importo * 0.0375; // 3,75%
        } elseif ($giorni <= 730) { // 1-2 anni
            return $importo * 0.0429; // 4,29%
        } else { // Oltre 2 anni
            return $importo * 0.05; // 5,00%
        }
    }

    /**
     * Calcola interessi legali (Codice 1944)
     * Base normativa: art. 6 D.Lgs. 471/1997 + tassi legali annuali MEF
     * Formula: importo × tasso_legale × (giorni_ritardo / 365)
     */
    protected function calcolaInteressi(float $importo, Carbon $scadenza, Carbon $dataOggi = null): float
    {
        if ($dataOggi === null) {
            $dataOggi = Carbon::now();
        }
        
        // Se non c'è ritardo, nessun interesse
        if ($dataOggi <= $scadenza) {
            return 0;
        }
        
        // Tassi legali ufficiali (fonte: MEF)
        $tassiLegali = [
            2023 => 0.05,   // 5,00%
            2024 => 0.025,  // 2,50%
            2025 => 0.02,   // 2,00%
        ];
        
        $interessiTotali = 0;
        $dataCorrente = $scadenza->copy()->addDay(); // Inizio calcolo dal giorno successivo alla scadenza
        
        while ($dataCorrente <= $dataOggi) {
            $annoCorrente = $dataCorrente->year;
            $tasso = $tassiLegali[$annoCorrente] ?? 0.02; // Default 2% se anno non trovato
            
            // Calcola la fine del periodo: o fine anno o data pagamento
            $fineAnno = Carbon::create($annoCorrente, 12, 31);
            $finePeriodo = $dataOggi < $fineAnno ? $dataOggi : $fineAnno;
            
            // Calcola giorni per questo periodo
            $giorniPeriodo = $dataCorrente->diffInDays($finePeriodo) + 1;
            
            // Calcola interessi per questo periodo
            $interessiPeriodo = $importo * $tasso * ($giorniPeriodo / 365);
            $interessiTotali += $interessiPeriodo;
            
            // Log per debug
            Log::info("Calcolo interessi segmentato", [
                'anno' => $annoCorrente,
                'tasso' => $tasso * 100 . '%',
                'giorni_periodo' => $giorniPeriodo,
                'interessi_periodo' => $interessiPeriodo,
                'data_inizio' => $dataCorrente->format('Y-m-d'),
                'data_fine' => $finePeriodo->format('Y-m-d')
            ]);
            
            // Passa al prossimo anno
            $dataCorrente = Carbon::create($annoCorrente + 1, 1, 1);
        }
        
        return $interessiTotali;
    }
    
    /**
     * Calcola interessi legacy (per compatibilità)
     * @deprecated Usa calcolaInteressi(importo, scadenza, dataOggi)
     */
    protected function calcolaInteressiLegacy(float $importo, int $giorni): float
    {
        $tassoLegale = 0.025; // 2.5% default
        return $importo * $tassoLegale * ($giorni / 365);
    }

    /**
     * Calcola i contributi INPS
     */
    protected function calcolaContributiInps(array $storico): float
    {
        // Calcola reddito imponibile
        $coefficiente = $this->company->coefficiente ?: 78.00;
        $redditoImponibile = $this->company->total_revenue * ($coefficiente / 100);

        if ($this->company->isGestioneSeparata()) {
            return $this->calcolaInpsGestioneSeparata($redditoImponibile, $storico);
        } else {
            return $this->calcolaInpsCommerciantiArtigiani($redditoImponibile, $storico);
        }
    }

    /**
     * Calcola INPS per Gestione Separata (professionisti)
     */
    protected function calcolaInpsGestioneSeparata($redditoImponibile, array $storico): float
    {
        // Recupera i parametri INPS per l'anno di competenza (anno precedente)
        $params = $this->getInpsParams($this->year - 1);
        
        // Applica agevolazione se presente
        if ($this->company->agevolazione_inps) {
            $aliquotaEffettiva = $params->aliquota_gestione_separata_ridotta;
        } else {
            $aliquotaEffettiva = $params->aliquota_gestione_separata;
        }
        
        $contributiTotali = $redditoImponibile * $aliquotaEffettiva;
        
        // Calcola saldo (inclusi arretrati)
        $saldoInps = $contributiTotali - $storico['acconti_inps_versati'] - $storico['credito_inps'];
        
        // Aggiungi gli importi INPS non pagati dell'anno precedente
        $saldoTotaleConArretrati = $saldoInps + $storico['acconti_inps_non_pagati'] + $storico['saldi_inps_non_pagati'];
        
        if ($saldoTotaleConArretrati < 0) {
            $nuovoCreditoInps = abs($saldoTotaleConArretrati);
            $saldoTotaleConArretrati = 0;
            
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_INPS_CREDITO,
                'tax_year' => $this->year - 1,
                'payment_year' => $this->year,
                'amount' => $nuovoCreditoInps,
                'payment_status' => Tax::STATUS_CREDIT,
                'description' => 'Credito INPS da riportare',
                'due_date' => Carbon::create($this->year, 6, 30)
            ]);
        }

        if ($saldoTotaleConArretrati > 0) {
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_INPS_SALDO,
                'tax_year' => $this->year - 1,
                'payment_year' => $this->year,
                'amount' => $saldoTotaleConArretrati,
                'description' => "Saldo contributi INPS Gestione Separata " . ($this->year - 1) . 
                    ($storico['acconti_inps_non_pagati'] > 0 || $storico['saldi_inps_non_pagati'] > 0 ? 
                    " (inclusi arretrati: €" . number_format($storico['acconti_inps_non_pagati'] + $storico['saldi_inps_non_pagati'], 2) . ")" : ""),
                'tax_code' => Tax::TAX_CODE_INPS_GESTIONE_SEPARATA,
                'due_date' => Carbon::create($this->year, 6, 30)
            ]);
        }
        
        // Acconti anno corrente (sempre basati sui contributi teorici)
        // Gli acconti rimangono sempre normali, indipendentemente dai pagamenti precedenti
        $inpsPrimoAcconto = $contributiTotali * 0.40;
        $inpsSecondoAcconto = $contributiTotali * 0.60;
        
        if ($inpsPrimoAcconto > 0) {
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_INPS_PRIMO_ACCONTO,
                'tax_year' => $this->year,
                'payment_year' => $this->year,
                'amount' => $inpsPrimoAcconto,
                'description' => "Primo acconto INPS Gestione Separata " . $this->year,
                'tax_code' => Tax::TAX_CODE_INPS_GESTIONE_SEPARATA,
                'due_date' => Carbon::create($this->year, 6, 30)
            ]);
        }

        if ($inpsSecondoAcconto > 0) {
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_INPS_SECONDO_ACCONTO,
                'tax_year' => $this->year,
                'payment_year' => $this->year,
                'amount' => $inpsSecondoAcconto,
                'description' => "Secondo acconto INPS Gestione Separata " . $this->year,
                'tax_code' => Tax::TAX_CODE_INPS_GESTIONE_SEPARATA,
                'due_date' => Carbon::create($this->year, 11, 30)
            ]);
        }
        
        // Restituisce il totale dei contributi calcolati per l'anno corrente
        return $contributiTotali;
    }

    /**
     * Calcola INPS per Commercianti/Artigiani - IMPLEMENTAZIONE DETTAGLIATA 
     * Segue la normativa ufficiale con tutti i parametri e calcoli trimestrali
     */
    protected function calcolaInpsCommerciantiArtigiani($redditoImponibile, array $storico): float
    {
        // PASSO 1: Carica parametri dell'anno di riferimento
        // Per i contributi dell'anno corrente (fissi e acconti) usiamo parametri anno corrente
        $paramsAnnoCorrente = $this->getInpsParams($this->year);
        
        // PASSO 2: Determina il reddito imponibile forfettario (già fornito come parametro)
        // Il reddito è già stato calcolato come: Fatturato × coefficiente redditività
        
        // PASSO 3: Calcola i contributi fissi per l'anno corrente (usa parametri anno corrente)
        $contributiFissi = $this->calcolaContributiFissiDettagliati($redditoImponibile, $paramsAnnoCorrente);
        
        // PASSO 4: Calcola l'eccedenza per acconti anno corrente
        $eccedenza = max(0, $redditoImponibile - $paramsAnnoCorrente->minimale_commercianti_artigiani);
        
        // PASSO 5: Calcola contributi percentuali su eccedenza per acconti anno corrente
        $contributiPercentuali = $this->calcolaContributiPercentualiDettagliati($eccedenza, $paramsAnnoCorrente);
        
        // PASSO 6: Applica il calcolo trimestrale (se attivo per l'anno)
        if ($paramsAnnoCorrente->calcolo_trimestrale_attivo && $contributiPercentuali > 0) {
            $contributiPercentuali = $this->applicaCalcoloTrimestrale($eccedenza, $paramsAnnoCorrente);
        }
        
        // PASSO 7: Somma contributi fissi + percentuali
        $totaleAnnuo = $contributiFissi + $contributiPercentuali;
        
        // PASSO 8: Genera le scadenze
        $this->generaScadenzeInpsDettagliate($contributiFissi, $contributiPercentuali, $paramsAnnoCorrente, $storico);
        
        // PASSO 9: Eventuali interessi e sanzioni (implementazione futura)
        // $this->calcolaInteressiESanzioni($storico);
        
        // Log dettagliato
        Log::info("Calcolo INPS Dettagliato Completato", [
            'company_id' => $this->company->id,
            'reddito_imponibile' => $redditoImponibile,
            'eccedenza' => $eccedenza,
            'contributi_fissi' => $contributiFissi,
            'contributi_percentuali' => $contributiPercentuali,
            'totale_annuo' => $totaleAnnuo,
            'calcolo_trimestrale' => $paramsAnnoCorrente->calcolo_trimestrale_attivo
        ]);
        
        return $totaleAnnuo;
    }

    /**
     * METODO DEPRECATO - sostituito dalla nuova implementazione modulare
     * Mantienuto temporaneamente per riferimento - da rimuovere
     */
    protected function OLD_calcolaInpsCommerciantiArtigiani_DEPRECATED()
    {
        // STEP 1: Contributi fissi sul minimale
        if ($this->company->agevolazione_inps) {
            // Con riduzione 35%
            $contributoFisso = $isCommerciante ? $params->contributo_fisso_commercianti_ridotto : $params->contributo_fisso_artigiani_ridotto;
            $aliquotaEccedenza = $isCommerciante ? $params->aliquota_commercianti_ridotta : $params->aliquota_artigiani_ridotta;
        } else {
            // Senza riduzione
            $contributoFisso = $isCommerciante ? $params->contributo_fisso_commercianti : $params->contributo_fisso_artigiani;
            $aliquotaEccedenza = $isCommerciante ? $params->aliquota_commercianti : $params->aliquota_artigiani;
        }
        
        // STEP 2: Contributi su eccedenza con aliquote scaglionate
        $contributoEccedenza = 0;
        if ($redditoImponibile > $params->minimale_commercianti_artigiani) {
            // Verifica se esistono aliquote maggiorate per l'anno
            $hasSogliaAliquotaMaggiorata = !empty($params->soglia_aliquota_maggiorata);
            
            if (!$hasSogliaAliquotaMaggiorata || $redditoImponibile <= $params->soglia_aliquota_maggiorata) {
                // Aliquota normale (semplice o fino alla soglia)
                $eccedenza = $redditoImponibile - $params->minimale_commercianti_artigiani;
                $aliquotaNormale = $this->company->agevolazione_inps 
                    ? ($isCommerciante ? $params->aliquota_commercianti_ridotta : $params->aliquota_artigiani_ridotta)
                    : ($isCommerciante ? $params->aliquota_commercianti : $params->aliquota_artigiani);
                $contributoEccedenza = $eccedenza * $aliquotaNormale;
            } else {
                // Aliquote scaglionate: normale fino a soglia + maggiorata oltre
                $eccedenzaPrimaSoglia = $params->soglia_aliquota_maggiorata - $params->minimale_commercianti_artigiani;
                $eccedenzaOltreSoglia = $redditoImponibile - $params->soglia_aliquota_maggiorata;
                
                // Aliquota normale (fino alla soglia)
                $aliquotaNormale = $this->company->agevolazione_inps 
                    ? ($isCommerciante ? $params->aliquota_commercianti_ridotta : $params->aliquota_artigiani_ridotta)
                    : ($isCommerciante ? $params->aliquota_commercianti : $params->aliquota_artigiani);
                
                // Aliquota maggiorata (oltre la soglia)
                $aliquotaMaggiorata = $this->company->agevolazione_inps 
                    ? ($isCommerciante ? $params->aliquota_commercianti_maggiorata_ridotta : $params->aliquota_artigiani_maggiorata_ridotta)
                    : ($isCommerciante ? $params->aliquota_commercianti_maggiorata : $params->aliquota_artigiani_maggiorata);
                
                $contributoEccedenza = ($eccedenzaPrimaSoglia * $aliquotaNormale) + ($eccedenzaOltreSoglia * $aliquotaMaggiorata);
            }
        }
        
        // STEP 3: Contributo maternità (sempre dovuto)
        $contributoMaternita = $params->contributo_maternita_annuo;
        
        // STEP 4: TOTALE contributi
        $contributiTotali = $contributoFisso + $contributoEccedenza + $contributoMaternita;
        
        // Log per debug
        Log::info("Calcolo INPS Commercianti/Artigiani CORRETTO", [
            'company_id' => $this->company->id,
            'reddito_imponibile' => $redditoImponibile,
            'contributo_fisso' => $contributoFisso,
            'contributo_eccedenza' => $contributoEccedenza,
            'contributo_maternita' => $contributoMaternita,
            'contributi_totali' => $contributiTotali,
            'agevolazione_35_percento' => $this->company->agevolazione_inps
        ]);
        
        // STEP 5: Calcola saldo SOLO per i contributi percentuali (eccedenza + maternità)
        // I contributi fissi hanno la loro gestione separata
        $contributiPercentualiDovutiAnnoPrecedente = $this->calcolaContributiInpsPercentualiAnnoPrecedente();
        $accontiPercentualiVersati = $this->calcolaAccontiPercentualiVersati();
        $saldoInpsPercentuali = $contributiPercentualiDovutiAnnoPrecedente - $accontiPercentualiVersati - $storico['credito_inps'];
        
        // Aggiungi solo gli importi INPS PERCENTUALI non pagati dell'anno precedente
        // I contributi fissi arretrati vengono gestiti separatamente
        $accontiPercentualiNonPagati = $this->calcolaAccontiPercentualiNonPagati();
        $saldiPercentualiNonPagati = $this->calcolaSaldiPercentualiNonPagati();
        $saldoInpsTotaleConArretrati = $saldoInpsPercentuali + $accontiPercentualiNonPagati + $saldiPercentualiNonPagati;
        
        // Log per debug
        Log::info("Debug Saldo INPS Percentuali", [
            'company_id' => $this->company->id,
            'contributi_percentuali_dovuti_anno_precedente' => $contributiPercentualiDovutiAnnoPrecedente,
            'acconti_percentuali_versati' => $accontiPercentualiVersati,
            'acconti_percentuali_non_pagati' => $accontiPercentualiNonPagati,
            'saldi_percentuali_non_pagati' => $saldiPercentualiNonPagati,
            'credito_inps' => $storico['credito_inps'],
            'saldo_percentuali_calcolato' => $saldoInpsPercentuali,
            'saldo_totale_con_arretrati' => $saldoInpsTotaleConArretrati
        ]);
        
        if ($saldoInpsTotaleConArretrati < 0) {
            $nuovoCreditoInps = abs($saldoInpsTotaleConArretrati);
            $saldoInpsTotaleConArretrati = 0;
            
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_INPS_CREDITO,
                'tax_year' => $this->year - 1,
                'payment_year' => $this->year,
                'amount' => $nuovoCreditoInps,
                'payment_status' => Tax::STATUS_CREDIT,
                'description' => 'Credito INPS da riportare',
                'due_date' => Carbon::create($this->year, 6, 30)
            ]);
        }

        // STEP 5.5: Gestisci contributi fissi arretrati (CFP)
        $this->generaContributiFissiArretrati($contributoFisso, $isCommerciante, $storico);
        
        // STEP 6: Contributi fissi trimestrali (CF) - 4 RATE TRIMESTRALI 2025
        // I contributi fissi sono già comprensivi di riduzione se applicabile
        $contributoFissoTrimestrale = $contributoFisso / 4;
        
        // Prima rata - scadenza 16 maggio
        $this->aggiungiRecord([
            'tax_type' => Tax::TAX_TYPE_INPS_FISSI_PRIMO_ACCONTO,
            'tax_year' => $this->year,
            'payment_year' => $this->year,
            'amount' => $contributoFissoTrimestrale,
            'description' => "1° rata contributi fissi INPS " . $this->year,
            'tax_code' => Tax::TAX_CODE_INPS_FISSI, // CF - codice unificato per contributi fissi
            'due_date' => Carbon::create($this->year, 5, 16)
        ]);
        
        // Seconda rata - scadenza 20 agosto
        $this->aggiungiRecord([
            'tax_type' => Tax::TAX_TYPE_INPS_FISSI_SECONDO_ACCONTO,
            'tax_year' => $this->year,
            'payment_year' => $this->year,
            'amount' => $contributoFissoTrimestrale,
            'description' => "2° rata contributi fissi INPS " . $this->year,
            'tax_code' => Tax::TAX_CODE_INPS_FISSI, // CF - codice unificato per contributi fissi
            'due_date' => Carbon::create($this->year, 8, 20)
        ]);
        
        // Terza rata - scadenza 17 novembre
        $this->aggiungiRecord([
            'tax_type' => Tax::TAX_TYPE_INPS_FISSI_TERZO_ACCONTO,
            'tax_year' => $this->year,
            'payment_year' => $this->year,
            'amount' => $contributoFissoTrimestrale,
            'description' => "3° rata contributi fissi INPS " . $this->year,
            'tax_code' => Tax::TAX_CODE_INPS_FISSI, // CF - codice unificato per contributi fissi
            'due_date' => Carbon::create($this->year, 11, 17)
        ]);
        
        // Quarta rata - scadenza 16 febbraio anno successivo
        $this->aggiungiRecord([
            'tax_type' => Tax::TAX_TYPE_INPS_FISSI_QUARTO_ACCONTO,
            'tax_year' => $this->year,
            'payment_year' => $this->year + 1,
            'amount' => $contributoFissoTrimestrale,
            'description' => "4° rata contributi fissi INPS " . $this->year,
            'tax_code' => Tax::TAX_CODE_INPS_FISSI, // CF - codice unificato per contributi fissi
            'due_date' => Carbon::create($this->year + 1, 2, 16)
        ]);
        
        // STEP 7: Contributi INPS percentuali totali (come nell'immagine) - SENZA RATEIZZAZIONE
        // NOTA: I contributi percentuali includono SOLO eccedenza (NON i fissi NÉ la maternità)
        // I contributi fissi e la maternità vengono pagati separatamente in 4 rate trimestrali
        $contributiInpsPercentualiTotali = $contributoEccedenza;
        
        // Saldo contributi INPS percentuali (inclusi arretrati se dovuti)
        if ($saldoInpsTotaleConArretrati > 0) {
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_INPS_PERCENTUALI_SALDO,
                'tax_year' => $this->year - 1,
                'payment_year' => $this->year,
                'amount' => $saldoInpsTotaleConArretrati,
                'description' => "Saldo contributi INPS percentuali " . ($this->year - 1) . 
                    ($accontiPercentualiNonPagati > 0 || $saldiPercentualiNonPagati > 0 ? 
                    " (inclusi arretrati: €" . number_format($accontiPercentualiNonPagati + $saldiPercentualiNonPagati, 2) . ")" : ""),
                'tax_code' => $isCommerciante ? Tax::TAX_CODE_INPS_PERCENTUALI_PREGRESSI_COMERCIANTI : Tax::TAX_CODE_INPS_PERCENTUALI_PREGRESSI_ARTIGIANI, // CPP/APP per anni pregressi
                'due_date' => Carbon::create($this->year, 8, 20) // Scadenza 20 agosto come nell'immagine
            ]);
        }
        
        // Acconti INPS percentuali (sempre basati sui contributi teorici)
        // Gli acconti rimangono sempre normali, indipendentemente dai pagamenti precedenti
        
        // 1° acconto contributi INPS percentuali
        $primoAccontoInpsPercentuali = $contributiInpsPercentualiTotali * 0.40; // 40% del totale
        if ($primoAccontoInpsPercentuali > 0) {
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_INPS_PERCENTUALI_PRIMO_ACCONTO,
                'tax_year' => $this->year,
                'payment_year' => $this->year,
                'amount' => $primoAccontoInpsPercentuali,
                'description' => "1° acconto contributi INPS percentuali " . $this->year,
                'tax_code' => $isCommerciante ? Tax::TAX_CODE_INPS_PERCENTUALI_COMERCIANTI : Tax::TAX_CODE_INPS_PERCENTUALI_ARTIGIANI,
                'due_date' => Carbon::create($this->year, 8, 20) // Scadenza 20 agosto come nell'immagine
            ]);
        }
        
        // 2° acconto contributi INPS percentuali
        $secondoAccontoInpsPercentuali = $contributiInpsPercentualiTotali * 0.60; // 60% del totale
        if ($secondoAccontoInpsPercentuali > 0) {
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_INPS_PERCENTUALI_SECONDO_ACCONTO,
                'tax_year' => $this->year,
                'payment_year' => $this->year,
                'amount' => $secondoAccontoInpsPercentuali,
                'description' => "2° acconto contributi INPS percentuali " . $this->year,
                'tax_code' => $isCommerciante ? Tax::TAX_CODE_INPS_PERCENTUALI_COMERCIANTI : Tax::TAX_CODE_INPS_PERCENTUALI_ARTIGIANI,
                'due_date' => Carbon::create($this->year, 12, 1) // Scadenza 1° dicembre come nell'immagine
            ]);
        }
        
        // Restituisce il totale dei contributi calcolati per l'anno corrente
        return $contributiInpsPercentualiTotali;
    }

    /**
     * PASSO 3: Calcola i contributi fissi dettagliati
     * Include: base minimale + maternità + addizionale IVS
     */
    protected function calcolaContributiFissiDettagliati(float $redditoImponibile, $params): float
    {
        $isCommerciante = $this->company->isCommerciante();
        
        // Base: reddito minimale × aliquota (con eventuale riduzione 35%)
        $aliquotaBase = $this->company->agevolazione_inps 
            ? ($isCommerciante ? $params->aliquota_commercianti_ridotta : $params->aliquota_artigiani_ridotta)
            : ($isCommerciante ? $params->aliquota_commercianti : $params->aliquota_artigiani);
            
        $contributoBase = $params->minimale_commercianti_artigiani * $aliquotaBase;
        
        // Contributo maternità (sempre dovuto)
        $contributoMaternita = $params->contributo_maternita_annuo;
        
        // Addizionale IVS sul minimale (se prevista)
        $addizionaleMinimale = $params->minimale_commercianti_artigiani * $params->addizionale_ivs_percentuale;
        
        $totaleContributiFissi = $contributoBase + $contributoMaternita + $addizionaleMinimale;
        
        Log::info("Calcolo Contributi Fissi Dettagliati", [
            'minimale' => $params->minimale_commercianti_artigiani,
            'aliquota_base' => $aliquotaBase,
            'contributo_base' => $contributoBase,
            'contributo_maternita' => $contributoMaternita,
            'addizionale_minimale' => $addizionaleMinimale,
            'totale_fissi' => $totaleContributiFissi
        ]);
        
        return $totaleContributiFissi;
    }
    
    /**
     * PASSO 5: Calcola contributi percentuali su eccedenza dettagliati
     * Include: eccedenza × aliquota + addizionale IVS + maggiorazione oltre massimale
     */
    protected function calcolaContributiPercentualiDettagliati(float $eccedenza, $params): float
    {
        if ($eccedenza <= 0) {
            return 0;
        }
        
        $isCommerciante = $this->company->isCommerciante();
        
        // Aliquota base (con eventuale riduzione 35%)
        $aliquotaBase = $this->company->agevolazione_inps 
            ? ($isCommerciante ? $params->aliquota_commercianti_ridotta : $params->aliquota_artigiani_ridotta)
            : ($isCommerciante ? $params->aliquota_commercianti : $params->aliquota_artigiani);
        
        // Calcolo con aliquote scaglionate basate sulla soglia INPS
        $redditoTotale = $params->minimale_commercianti_artigiani + $eccedenza;
        $contributoEccedenza = 0;
        
        if (!empty($params->soglia_aliquota_maggiorata) && $redditoTotale > $params->soglia_aliquota_maggiorata) {
            // CALCOLO SCAGLIONATO: parte fino alla soglia + parte oltre la soglia
            $eccedenzaFinoSoglia = $params->soglia_aliquota_maggiorata - $params->minimale_commercianti_artigiani;
            $eccedenzaOltreSoglia = $redditoTotale - $params->soglia_aliquota_maggiorata;
            
            // Aliquota normale fino alla soglia
            $aliquotaNormale = $this->company->agevolazione_inps 
                ? ($isCommerciante ? $params->aliquota_commercianti_ridotta : $params->aliquota_artigiani_ridotta)
                : ($isCommerciante ? $params->aliquota_commercianti : $params->aliquota_artigiani);
                
            // Aliquota maggiorata oltre la soglia  
            $aliquotaMaggiorata = $this->company->agevolazione_inps 
                ? ($isCommerciante ? $params->aliquota_commercianti_maggiorata_ridotta : $params->aliquota_artigiani_maggiorata_ridotta)
                : ($isCommerciante ? $params->aliquota_commercianti_maggiorata : $params->aliquota_artigiani_maggiorata);
            
            $contributoNormale = $eccedenzaFinoSoglia * $aliquotaNormale;
            $contributoMaggiorato = $eccedenzaOltreSoglia * $aliquotaMaggiorata;
            $contributoEccedenza = $contributoNormale + $contributoMaggiorato;
            
            Log::info("Calcolo Scaglionato INPS", [
                'reddito_totale' => $redditoTotale,
                'soglia' => $params->soglia_aliquota_maggiorata,
                'eccedenza_fino_soglia' => $eccedenzaFinoSoglia,
                'eccedenza_oltre_soglia' => $eccedenzaOltreSoglia,
                'aliquota_normale' => $aliquotaNormale,
                'aliquota_maggiorata' => $aliquotaMaggiorata,
                'contributo_normale' => $contributoNormale,
                'contributo_maggiorato' => $contributoMaggiorato
            ]);
        } else {
            // CALCOLO SEMPLICE: aliquota unica su tutta l'eccedenza
            $contributoEccedenza = $eccedenza * $aliquotaBase;
        }
        
        // Addizionale IVS su tutta l'eccedenza
        $addizionaleEccedenza = $eccedenza * $params->addizionale_ivs_percentuale;
        
        // Maggiorazione oltre massimale reddituale (diversa dalla soglia INPS)
        $maggiorazione = 0;
        if ($redditoTotale > $params->massimale_reddituale) {
            $eccedenzaOltreMassimale = $redditoTotale - $params->massimale_reddituale;
            $maggiorazione = $eccedenzaOltreMassimale * $params->maggiorazione_oltre_massimale;
        }
        
        $totalePercentuali = $contributoEccedenza + $addizionaleEccedenza + $maggiorazione;
        
        Log::info("Calcolo Contributi Percentuali Dettagliati", [
            'eccedenza' => $eccedenza,
            'aliquota_base' => $aliquotaBase,
            'contributo_eccedenza' => $contributoEccedenza,
            'addizionale_eccedenza' => $addizionaleEccedenza,
            'maggiorazione' => $maggiorazione,
            'totale_percentuali' => $totalePercentuali
        ]);
        
        return $totalePercentuali;
    }
    
    /**
     * PASSO 6: Applica il calcolo trimestrale con arrotondamenti
     */
    protected function applicaCalcoloTrimestrale(float $eccedenza, $params): float
    {
        $isCommerciante = $this->company->isCommerciante();
        
        // Aliquota base
        $aliquotaBase = $this->company->agevolazione_inps 
            ? ($isCommerciante ? $params->aliquota_commercianti_ridotta : $params->aliquota_artigiani_ridotta)
            : ($isCommerciante ? $params->aliquota_commercianti : $params->aliquota_artigiani);
        
        // Dividi eccedenza per 4 trimestri
        $eccedenzaTrimestrale = $eccedenza / 4;
        
        $totaleTrimestri = 0;
        for ($trimestre = 1; $trimestre <= 4; $trimestre++) {
            // Calcola contributo per trimestre
            $contributoTrimestre = $eccedenzaTrimestrale * $aliquotaBase;
            $addizionaleTrimestre = $eccedenzaTrimestrale * $params->addizionale_ivs_percentuale;
            
            // Arrotonda al centesimo ogni trimestre
            $contributoTrimestre = round($contributoTrimestre + $addizionaleTrimestre, 2);
            $totaleTrimestri += $contributoTrimestre;
        }
        
        Log::info("Calcolo Trimestrale Applicato", [
            'eccedenza_totale' => $eccedenza,
            'eccedenza_trimestrale' => $eccedenzaTrimestrale,
            'totale_trimestri' => $totaleTrimestri,
            'differenza_vs_diretto' => $totaleTrimestri - ($eccedenza * ($aliquotaBase + $params->addizionale_ivs_percentuale))
        ]);
        
        return $totaleTrimestri;
    }
    
    /**
     * PASSO 8: Genera le scadenze INPS dettagliate
     */
    protected function generaScadenzeInpsDettagliate(float $contributiFissi, float $contributiPercentuali, $params, array $storico): void
    {
        // Genera contributi fissi (CF) - 4 rate trimestrali
        $this->generaScadenzeContributiFissi($contributiFissi);
        
        // Calcola e genera saldo percentuali (CPP)
        $saldoPercentuali = $this->calcolaSaldoPercentualiDettagliato($storico);
        if ($saldoPercentuali > 0) {
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_INPS_PERCENTUALI_SALDO,
                'tax_year' => $this->year - 1,
                'payment_year' => $this->year,
                'amount' => $saldoPercentuali,
                'description' => 'Saldo contributi INPS percentuali ' . ($this->year - 1),
                'tax_code' => Tax::TAX_CODE_INPS_PERCENTUALI_SALDO, // CPP
                'due_date' => Carbon::create($this->year, 8, 20)
            ]);
        }
        
        // Genera acconti percentuali (CP)
        $this->generaAccontiPercentuali($contributiPercentuali);
        
        // Genera eventuali arretrati (CRN) - implementazione futura
        // $this->generaArretratiCRN($storico);
    }
    
    /**
     * Genera le 4 rate trimestrali per contributi fissi (CF)
     */
    protected function generaScadenzeContributiFissi(float $contributiFissi): void
    {
        $rataTrimestrale = round($contributiFissi / 4, 2);
        
        // 1° rata - 16 maggio
        $this->aggiungiRecord([
            'tax_type' => Tax::TAX_TYPE_INPS_FISSI_PRIMO_ACCONTO,
            'tax_year' => $this->year,
            'payment_year' => $this->year,
            'amount' => $rataTrimestrale,
            'description' => "1° rata contributi fissi INPS " . $this->year,
            'tax_code' => Tax::TAX_CODE_INPS_FISSI, // CF
            'due_date' => Carbon::create($this->year, 5, 16)
        ]);
        
        // 2° rata - 20 agosto  
        $this->aggiungiRecord([
            'tax_type' => Tax::TAX_TYPE_INPS_FISSI_SECONDO_ACCONTO,
            'tax_year' => $this->year,
            'payment_year' => $this->year,
            'amount' => $rataTrimestrale,
            'description' => "2° rata contributi fissi INPS " . $this->year,
            'tax_code' => Tax::TAX_CODE_INPS_FISSI, // CF
            'due_date' => Carbon::create($this->year, 8, 20)
        ]);
        
        // 3° rata - 17 novembre
        $this->aggiungiRecord([
            'tax_type' => Tax::TAX_TYPE_INPS_FISSI_TERZO_ACCONTO,
            'tax_year' => $this->year,
            'payment_year' => $this->year,
            'amount' => $rataTrimestrale,
            'description' => "3° rata contributi fissi INPS " . $this->year,
            'tax_code' => Tax::TAX_CODE_INPS_FISSI, // CF
            'due_date' => Carbon::create($this->year, 11, 17)
        ]);
        
        // 4° rata - 16 febbraio anno successivo
        $this->aggiungiRecord([
            'tax_type' => Tax::TAX_TYPE_INPS_FISSI_QUARTO_ACCONTO,
            'tax_year' => $this->year,
            'payment_year' => $this->year + 1,
            'amount' => $rataTrimestrale,
            'description' => "4° rata contributi fissi INPS " . $this->year,
            'tax_code' => Tax::TAX_CODE_INPS_FISSI, // CF
            'due_date' => Carbon::create($this->year + 1, 2, 16)
        ]);
    }
    
    /**
     * Calcola saldo percentuali usando il nuovo sistema dettagliato
     */
    protected function calcolaSaldoPercentualiDettagliato(array $storico): float
    {
        // Calcola i contributi percentuali dovuti per l'anno precedente usando il nuovo metodo dettagliato
        $paramsAnnoPrecedente = $this->getInpsParams($this->year - 1);
        
        // Calcola il fatturato dell'anno precedente
        $totalRevenuePrecedente = $this->calculateRevenueForYear($this->company, $this->year - 1);
        
        if ($totalRevenuePrecedente <= 0) {
            $contributiDovuti = 0;
        } else {
            // Calcola reddito imponibile anno precedente
            $coefficiente = $this->company->coefficiente ?: 78.00;
            $redditoImponibilePrecedente = $totalRevenuePrecedente * ($coefficiente / 100);
            
            // Calcola eccedenza per anno precedente
            $eccedenzaPrecedente = max(0, $redditoImponibilePrecedente - $paramsAnnoPrecedente->minimale_commercianti_artigiani);
            
            // USA IL NUOVO METODO DETTAGLIATO che include le addizionali
            $contributiDovuti = $this->calcolaContributiPercentualiDettagliati($eccedenzaPrecedente, $paramsAnnoPrecedente);
        }
        
        $accontiVersati = $this->calcolaAccontiPercentualiVersati();
        $arretrati = $this->calcolaAccontiPercentualiNonPagati() + $this->calcolaSaldiPercentualiNonPagati();
        
        $saldo = $contributiDovuti - $accontiVersati - $storico['credito_inps'] + $arretrati;
        
        Log::info("Saldo Percentuali Dettagliato (CON ADDIZIONALI)", [
            'contributi_dovuti_con_addizionali' => $contributiDovuti,
            'acconti_versati' => $accontiVersati,
            'arretrati' => $arretrati,
            'credito_inps' => $storico['credito_inps'],
            'saldo_finale' => max(0, $saldo)
        ]);
        
        return max(0, $saldo);
    }
    
    /**
     * Genera acconti percentuali (CP) - 40% e 60%
     */
    protected function generaAccontiPercentuali(float $contributiPercentuali): void
    {
        if ($contributiPercentuali <= 0) {
            return;
        }
        
        // 1° acconto (40%) - 20 agosto
        $primoAcconto = round($contributiPercentuali * 0.40, 2);
        $this->aggiungiRecord([
            'tax_type' => Tax::TAX_TYPE_INPS_PERCENTUALI_PRIMO_ACCONTO,
            'tax_year' => $this->year,
            'payment_year' => $this->year,
            'amount' => $primoAcconto,
            'description' => '1° acconto contributi INPS percentuali ' . $this->year,
            'tax_code' => Tax::TAX_CODE_INPS_PERCENTUALI_ACCONTO, // CP
            'due_date' => Carbon::create($this->year, 8, 20)
        ]);
        
        // 2° acconto (60%) - 16 dicembre
        $secondoAcconto = round($contributiPercentuali * 0.60, 2);
        $this->aggiungiRecord([
            'tax_type' => Tax::TAX_TYPE_INPS_PERCENTUALI_SECONDO_ACCONTO,
            'tax_year' => $this->year,
            'payment_year' => $this->year,
            'amount' => $secondoAcconto,
            'description' => '2° acconto contributi INPS percentuali ' . $this->year,
            'tax_code' => Tax::TAX_CODE_INPS_PERCENTUALI_ACCONTO, // CP
            'due_date' => Carbon::create($this->year, 12, 16)
        ]);
    }

    /**
     * Cancella o marca come CANCELLED solo i record che verranno sostituiti
     * Mantiene i record pending che rappresentano arretrati da includere nei nuovi calcoli
     */
    protected function cancellaRecordPending(): void
    {
        Log::info("🔄 Cancellazione record pending automatici per company {$this->company->id}, anno {$this->year}");
        
        // Cancella solo i record dell'anno corrente calcolati automaticamente (NON quelli caricati manualmente)
        $cancellatiAnnoCorrente = $this->company->taxes()
            ->where('tax_year', $this->year)
            ->where('payment_status', Tax::STATUS_PENDING)
            ->where('is_manual', false) // NON cancellare quelli caricati manualmente
            ->update(['payment_status' => Tax::STATUS_CANCELLED]);
            
        Log::info("📊 Cancellati {$cancellatiAnnoCorrente} record pending automatici anno {$this->year}");
            
        // Cancella i saldi dell'anno precedente calcolati automaticamente
        $cancellatiAnnoPrecedente = $this->company->taxes()
            ->where('tax_year', $this->year - 1)
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SALDO,
                Tax::TAX_TYPE_INPS_SALDO,
                Tax::TAX_TYPE_INPS_FISSI_SALDO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_SALDO
            ])
            ->where('payment_status', Tax::STATUS_PENDING)
            ->where('is_manual', false) // NON cancellare quelli caricati manualmente
            ->update(['payment_status' => Tax::STATUS_CANCELLED]);
            
        Log::info("📊 Cancellati {$cancellatiAnnoPrecedente} record pending automatici anno " . ($this->year - 1));
    }

    /**
     * Cancella solo i record pending automatici (NON quelli manuali e NON quelli pagati)
     */
    protected function cancellaRecordPendingWithoutChangingPaid(): void
    {
        Log::info("🔄 Cancellazione record pending automatici (senza toccare quelli pagati) per company {$this->company->id}, anno {$this->year}");
        
        // Cancella solo i record dell'anno corrente calcolati automaticamente e PENDING (NON quelli manuali e NON quelli pagati)
        $cancellatiAnnoCorrente = $this->company->taxes()
            ->where('tax_year', $this->year)
            ->where('payment_status', Tax::STATUS_PENDING)
            ->where('is_manual', false) // NON cancellare quelli caricati manualmente
            ->update(['payment_status' => Tax::STATUS_CANCELLED]);
            
        Log::info("📊 Cancellati {$cancellatiAnnoCorrente} record pending automatici anno {$this->year}");
            
        // Cancella i saldi dell'anno precedente calcolati automaticamente e PENDING
        $cancellatiAnnoPrecedente = $this->company->taxes()
            ->where('tax_year', $this->year - 1)
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SALDO,
                Tax::TAX_TYPE_INPS_SALDO,
                Tax::TAX_TYPE_INPS_FISSI_SALDO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_SALDO
            ])
            ->where('payment_status', Tax::STATUS_PENDING)
            ->where('is_manual', false) // NON cancellare quelli caricati manualmente
            ->update(['payment_status' => Tax::STATUS_CANCELLED]);
            
        Log::info("📊 Cancellati {$cancellatiAnnoPrecedente} record pending automatici anno " . ($this->year - 1));
        
        // Log delle tasse preservate
        $tassePagate = $this->company->taxes()
            ->where('payment_year', $this->year)
            ->where('payment_status', Tax::STATUS_PAID)
            ->count();
            
        $tasseManuali = $this->company->taxes()
            ->where('payment_year', $this->year)
            ->where('is_manual', true)
            ->count();
            
        Log::info("🛡️ Tasse preservate", [
            'tasse_pagate' => $tassePagate,
            'tasse_manuali' => $tasseManuali
        ]);
    }

    /**
     * Salva i record delle tasse nel database, evitando duplicati con quelle caricate manualmente
     */
    protected function salvaRecordTasse(): void
    {
        $salvati = 0;
        $saltati = 0;
        
        foreach ($this->taxRecords as $record) {
            $record['company_id'] = $this->company->id;
            
            // Controlla se esiste già una tassa manuale per lo stesso codice tributo e anno
            if ($this->esisteTassaManuale($record)) {
                Log::info("⏭️ Salta creazione tassa automatica - esiste già tassa manuale", [
                    'tax_code' => $record['tax_code'] ?? 'N/A',
                    'tax_type' => $record['tax_type'],
                    'tax_year' => $record['tax_year'],
                    'amount' => $record['amount']
                ]);
                $saltati++;
                continue;
            }
            
            // Imposta is_manual = false per le tasse calcolate automaticamente
            $record['is_manual'] = false;
            
            Tax::create($record);
            $salvati++;
        }
        
        Log::info("💾 Salvataggio record tasse completato", [
            'salvati' => $salvati,
            'saltati' => $saltati,
            'totali' => count($this->taxRecords)
        ]);
    }

    /**
     * Controlla se esiste già una tassa caricata manualmente per lo stesso codice tributo e anno
     */
    protected function esisteTassaManuale(array $record): bool
    {
        $query = $this->company->taxes()
            ->where('tax_year', $record['tax_year'])
            ->where('is_manual', true); // Solo tasse caricate manualmente
        
        // Se abbiamo un codice tributo, controlla per quello
        if (!empty($record['tax_code'])) {
            $query->where('tax_code', $record['tax_code']);
        } else {
            // Altrimenti controlla per tipo di tassa
            $query->where('tax_type', $record['tax_type']);
        }
        
        return $query->exists();
    }

    /**
     * Aggiunge un record di tassa all'array
     */
    protected function aggiungiRecord(array $data): void
    {
        $data['amount'] = round($data['amount'], 2);
        $data['payment_status'] = $data['payment_status'] ?? Tax::STATUS_PENDING;
        $this->taxRecords[] = $data;
    }

    /**
     * Calcola il fatturato per un determinato anno dalle fatture
     */
    protected function calculateRevenueForYear(Company $company, int $year): float
    {
        // Fatture emesse nell'anno
        $fatture = $company->invoices()
            ->whereYear('issue_date', $year)
            ->where('document_type', '!=', 'TD04') // Escludi note di credito
            ->sum('total');

        // Note di credito nell'anno
        $noteCredito = $company->invoices()
            ->whereYear('issue_date', $year)
            ->where('document_type', 'TD04') // Solo note di credito
            ->sum('total');

        // Fatturato netto
        return $fatture - $noteCredito;
    }

    /**
     * Calcola i contributi INPS dovuti per l'anno precedente (non pagati, ma dovuti)
     */
    protected function calcolaContributiInpsAnnoPrecedente(): float
    {
        // Calcola il fatturato dell'anno precedente
        $totalRevenuePrecedente = $this->calculateRevenueForYear($this->company, $this->year - 1);
        
        if ($totalRevenuePrecedente <= 0) {
            return 0;
        }
        
        // Calcola reddito imponibile anno precedente
        $coefficiente = $this->company->coefficiente ?: 78.00;
        $redditoImponibilePrecedente = $totalRevenuePrecedente * ($coefficiente / 100);
        
        if ($this->company->isGestioneSeparata()) {
            // Gestione Separata
            $params = $this->getInpsParams($this->year - 1);
            
            if ($this->company->agevolazione_inps) {
                $aliquotaEffettiva = $params->aliquota_gestione_separata_ridotta;
            } else {
                $aliquotaEffettiva = $params->aliquota_gestione_separata;
            }
            
            return $redditoImponibilePrecedente * $aliquotaEffettiva;
            
        } else {
            // Commercianti/Artigiani
            $params = $this->getInpsParams($this->year - 1);
            $isCommerciante = $this->company->isCommerciante();
            
            // Applica massimale se necessario
            if ($redditoImponibilePrecedente > $params->massimale_commercianti_artigiani) {
                $redditoImponibilePrecedente = $params->massimale_commercianti_artigiani;
            }
            
            // Contributi fissi sul minimale
            if ($this->company->agevolazione_inps) {
                $contributoFisso = $isCommerciante ? $params->contributo_fisso_commercianti_ridotto : $params->contributo_fisso_artigiani_ridotto;
                $aliquotaEccedenza = $isCommerciante ? $params->aliquota_commercianti_ridotta : $params->aliquota_artigiani_ridotta;
            } else {
                $contributoFisso = $isCommerciante ? $params->contributo_fisso_commercianti : $params->contributo_fisso_artigiani;
                $aliquotaEccedenza = $isCommerciante ? $params->aliquota_commercianti : $params->aliquota_artigiani;
            }
            
            // Contributi su eccedenza
            $contributoEccedenza = 0;
            if ($redditoImponibilePrecedente > $params->minimale_commercianti_artigiani) {
                $eccedenza = $redditoImponibilePrecedente - $params->minimale_commercianti_artigiani;
                $contributoEccedenza = $eccedenza * $aliquotaEccedenza;
            }
            
            // Contributo maternità
            $contributoMaternita = $params->contributo_maternita_annuo;
            
            return $contributoFisso + $contributoEccedenza + $contributoMaternita;
        }
    }

    /**
     * Calcola i contributi INPS pagati in un anno specifico (criterio di cassa)
     */
    protected function calcolaContributiInpsPagatiAnno(int $year): float
    {
        // CRITERIO DI CASSA: recupera contributi INPS pagati nell'anno specificato
        // Non conta il tax_year, conta quando sono stati effettivamente pagati (paid_date)
        $contributiPagati = $this->company->taxes()
            ->whereYear('paid_date', $year) // CORRETTO: usa paid_date
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_INPS_SALDO,
                Tax::TAX_TYPE_INPS_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_SECONDO_ACCONTO,
                Tax::TAX_TYPE_INPS_TERZO_ACCONTO,
                Tax::TAX_TYPE_INPS_QUARTO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_SALDO,
                Tax::TAX_TYPE_INPS_FISSI_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_SECONDO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_TERZO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_QUARTO_ACCONTO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_SALDO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_SECONDO_ACCONTO
            ])
            ->where('payment_status', Tax::STATUS_PAID)
            ->whereNotNull('paid_date') // Assicura che ci sia una data di pagamento
            ->sum('amount');

        return $contributiPagati;
    }

    /**
     * Calcola i contributi INPS pagati nell'anno precedente (criterio di cassa)
     */
    protected function calcolaContributiInpsPagatiAnnoPrecedente(): float
    {
        // CRITERIO DI CASSA: usa paid_date invece di tax_year
        return $this->calcolaContributiInpsPagatiAnno($this->year - 1);
    }

    /**
     * Genera contributi fissi arretrati (CFP/AFP) per anni pregressi non pagati
     */
    protected function generaContributiFissiArretrati(float $contributoFissoAnnuale, bool $isCommerciante, array $storico): void
    {
        // Verifica se ci sono contributi fissi non pagati dell'anno precedente
        $contributiFissiNonPagati = $this->company->taxes()
            ->where('tax_year', $this->year - 1)
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_INPS_FISSI_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_SECONDO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_TERZO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_QUARTO_ACCONTO
            ])
            ->where('payment_status', Tax::STATUS_PENDING)
            ->sum('amount');

        if ($contributiFissiNonPagati > 0) {
            // Genera bollettino per contributi fissi arretrati con codice CFP/AFP
            $this->aggiungiRecord([
                'tax_type' => Tax::TAX_TYPE_INPS_FISSI_SALDO,
                'tax_year' => $this->year - 1,
                'payment_year' => $this->year,
                'amount' => $contributiFissiNonPagati,
                'description' => "Contributi fissi INPS " . ($this->year - 1) . " (anni pregressi)",
                'tax_code' => $isCommerciante ? Tax::TAX_CODE_INPS_FISSI_PREGRESSI_COMERCIANTI : Tax::TAX_CODE_INPS_FISSI_PREGRESSI_ARTIGIANI, // CFP/AFP
                'due_date' => Carbon::create($this->year, 8, 20) // Stessa scadenza del saldo percentuali
            ]);

            // TODO: Aggiungi sanzioni e interessi per contributi fissi arretrati
            // Utilizzando codici 1989 (sanzioni INPS) e 1990 (interessi INPS)
        }
    }

    /**
     * Calcola gli acconti INPS percentuali non pagati (esclude i fissi)
     */
    protected function calcolaAccontiPercentualiNonPagati(): float
    {
        return $this->company->taxes()
            ->where('tax_year', $this->year - 1)
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_INPS_PERCENTUALI_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_SECONDO_ACCONTO,
                // Esclude i contributi fissi:
                // Tax::TAX_TYPE_INPS_FISSI_PRIMO_ACCONTO,
                // Tax::TAX_TYPE_INPS_FISSI_SECONDO_ACCONTO,
                // Tax::TAX_TYPE_INPS_FISSI_TERZO_ACCONTO,
                // Tax::TAX_TYPE_INPS_FISSI_QUARTO_ACCONTO,
            ])
            ->where('payment_status', Tax::STATUS_PENDING)
            ->sum('amount');
    }

    /**
     * Calcola i saldi INPS percentuali non pagati (esclude i fissi)
     */
    protected function calcolaSaldiPercentualiNonPagati(): float
    {
        return $this->company->taxes()
            ->where('tax_year', $this->year - 2) // L'anno prima del precedente
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_INPS_PERCENTUALI_SALDO,
                // Esclude i contributi fissi:
                // Tax::TAX_TYPE_INPS_FISSI_SALDO,
            ])
            ->where('payment_status', Tax::STATUS_PENDING)
            ->sum('amount');
    }

    /**
     * Calcola i contributi INPS percentuali dovuti per l'anno precedente (solo eccedenza + maternità)
     */
    protected function calcolaContributiInpsPercentualiAnnoPrecedente(): float
    {
        // Calcola il fatturato dell'anno precedente
        $totalRevenuePrecedente = $this->calculateRevenueForYear($this->company, $this->year - 1);
        
        if ($totalRevenuePrecedente <= 0) {
            return 0;
        }
        
        // Calcola reddito imponibile anno precedente
        $coefficiente = $this->company->coefficiente ?: 78.00;
        $redditoImponibilePrecedente = $totalRevenuePrecedente * ($coefficiente / 100);
        
        if ($this->company->isGestioneSeparata()) {
            // Gestione Separata: tutto è percentuale (non ha contributi fissi)
            $params = $this->getInpsParams($this->year - 1);
            
            if ($this->company->agevolazione_inps) {
                $aliquotaEffettiva = $params->aliquota_gestione_separata_ridotta;
            } else {
                $aliquotaEffettiva = $params->aliquota_gestione_separata;
            }
            
            return $redditoImponibilePrecedente * $aliquotaEffettiva;
            
        } else {
            // Commercianti/Artigiani: SOLO eccedenza + maternità (NON i fissi)
            $params = $this->getInpsParams($this->year - 1);
            $isCommerciante = $this->company->isCommerciante();
            
            // Applica massimale se necessario
            if ($redditoImponibilePrecedente > $params->massimale_commercianti_artigiani) {
                $redditoImponibilePrecedente = $params->massimale_commercianti_artigiani;
            }
            
            // Contributi su eccedenza (NON i fissi)
            $contributoEccedenza = 0;
            if ($redditoImponibilePrecedente > $params->minimale_commercianti_artigiani) {
                $eccedenza = $redditoImponibilePrecedente - $params->minimale_commercianti_artigiani;
                
                if ($this->company->agevolazione_inps) {
                    $aliquotaEccedenza = $isCommerciante ? $params->aliquota_commercianti_ridotta : $params->aliquota_artigiani_ridotta;
                } else {
                    $aliquotaEccedenza = $isCommerciante ? $params->aliquota_commercianti : $params->aliquota_artigiani;
                }
                
                $contributoEccedenza = $eccedenza * $aliquotaEccedenza;
            }
            
            // NOTA: Il contributo maternità è già incluso nei contributi fissi
            // Non va aggiunto qui per evitare doppio conteggio
            
            // SOLO eccedenza (esclude fissi e maternità)
            return $contributoEccedenza;
        }
    }

    /**
     * Calcola gli acconti INPS percentuali versati (esclude i fissi)
     */
    protected function calcolaAccontiPercentualiVersati(): float
    {
        return $this->company->taxes()
            ->where('tax_year', $this->year - 1)
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_INPS_PERCENTUALI_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_SECONDO_ACCONTO,
                // Esclude i contributi fissi:
                // Tax::TAX_TYPE_INPS_FISSI_PRIMO_ACCONTO,
                // Tax::TAX_TYPE_INPS_FISSI_SECONDO_ACCONTO,
                // Tax::TAX_TYPE_INPS_FISSI_TERZO_ACCONTO,
                // Tax::TAX_TYPE_INPS_FISSI_QUARTO_ACCONTO,
            ])
            ->where('payment_status', Tax::STATUS_PAID)
            ->sum('amount');
    }

    /**
     * Log dell'operazione completata
     */
    protected function logOperazione(): void
    {
        $coefficiente = $this->company->coefficiente ?: 78.00;
        $redditoLordo = $this->company->total_revenue * ($coefficiente / 100);
        $contributiInpsPagati = $this->calcolaContributiInpsPagatiAnnoPrecedente();
        $redditoImponibile = $redditoLordo - $contributiInpsPagati;
        $aliquota = $this->company->startup ? 0.05 : 0.15;
        $impostaTotale = $redditoImponibile * $aliquota;

        Log::info("Calcolo tasse completato", [
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'year' => $this->year,
            'total_revenue' => $this->company->total_revenue,
            'coefficiente' => $coefficiente,
            'reddito_lordo' => $redditoLordo,
            'contributi_inps_pagati' => $contributiInpsPagati,
            'reddito_imponibile' => $redditoImponibile,
            'aliquota' => $aliquota,
            'imposta_totale' => $impostaTotale,
            'startup' => $this->company->startup,
            'gestione_separata' => $this->company->gestione_separata,
            'agevolazione_inps' => $this->company->agevolazione_inps,
            'records_created' => count($this->taxRecords)
        ]);
    }

    /**
     * Crea acconti versati anni precedenti (per prima elaborazione)
     */
    public function createPreviousYearAcconti(Company $company, int $year, float $primoAcconto, float $secondoAcconto): void
    {
        if ($primoAcconto > 0) {
            Tax::create([
                'company_id' => $company->id,
                'tax_year' => $year,
                'payment_year' => $year,
                'tax_type' => Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO,
                'description' => "Primo acconto imposta sostitutiva {$year} (importato)",
                'tax_code' => Tax::TAX_CODE_IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO,
                'amount' => $primoAcconto,
                'due_date' => Carbon::create($year, 6, 30),
                'payment_status' => Tax::STATUS_PENDING,
                'paid_date' => null,
                'notes' => 'Importato da prima elaborazione - VERIFICARE SE EFFETTIVAMENTE PAGATO'
            ]);
        }

        if ($secondoAcconto > 0) {
            Tax::create([
                'company_id' => $company->id,
                'tax_year' => $year,
                'payment_year' => $year,
                'tax_type' => Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO,
                'description' => "Secondo acconto imposta sostitutiva {$year} (importato)",
                'tax_code' => Tax::TAX_CODE_IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO,
                'amount' => $secondoAcconto,
                'due_date' => Carbon::create($year, 11, 30),
                'payment_status' => Tax::STATUS_PENDING,
                'paid_date' => null,
                'notes' => 'Importato da prima elaborazione - VERIFICARE SE EFFETTIVAMENTE PAGATO'
            ]);
        }
    }

    /**
     * Crea crediti da anni precedenti (per prima elaborazione)
     */
    public function createPreviousYearCredits(Company $company, int $year, float $creditoImposta, float $creditoInps): void
    {
        if ($creditoImposta > 0) {
            Tax::create([
                'company_id' => $company->id,
                'tax_year' => $year,
                'payment_year' => $year + 1,
                'tax_type' => Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_CREDITO,
                'description' => "Credito imposta sostitutiva da anni precedenti",
                'amount' => $creditoImposta,
                'due_date' => Carbon::create($year + 1, 6, 30),
                'payment_status' => Tax::STATUS_CREDIT,
                'notes' => 'Importato da prima elaborazione'
            ]);
        }

        if ($creditoInps > 0) {
            Tax::create([
                'company_id' => $company->id,
                'tax_year' => $year,
                'payment_year' => $year + 1,
                'tax_type' => Tax::TAX_TYPE_INPS_CREDITO,
                'description' => "Credito INPS da anni precedenti",
                'amount' => $creditoInps,
                'due_date' => Carbon::create($year + 1, 6, 30),
                'payment_status' => Tax::STATUS_CREDIT,
                'notes' => 'Importato da prima elaborazione'
            ]);
        }
    }
    
    /**
     * Calcola i contributi INPS effettivamente versati per un anno specifico
     * Usato per la deduzione dall'imposta sostitutiva (criterio di cassa)
     */
    protected function calcolaContributiInpsVersatiPerAnno(int $anno): float
    {
        $accontiInpsVersati = $this->company->taxes()
            ->where('tax_year', $anno)
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_INPS_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_SECONDO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_SECONDO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_TERZO_ACCONTO,
                Tax::TAX_TYPE_INPS_FISSI_QUARTO_ACCONTO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_PRIMO_ACCONTO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_SECONDO_ACCONTO
            ])
            ->where('payment_status', Tax::STATUS_PAID)
            ->sum('amount');

        $saldiInpsVersati = $this->company->taxes()
            ->where('tax_year', $anno)
            ->whereIn('tax_type', [
                Tax::TAX_TYPE_INPS_SALDO,
                Tax::TAX_TYPE_INPS_PERCENTUALI_SALDO
            ])
            ->where('payment_status', Tax::STATUS_PAID)
            ->sum('amount');

        $totaleVersato = $accontiInpsVersati + $saldiInpsVersati;
        
        Log::info("Contributi INPS versati per anno $anno", [
            'company_id' => $this->company->id,
            'anno' => $anno,
            'acconti_versati' => $accontiInpsVersati,
            'saldi_versati' => $saldiInpsVersati,
            'totale_versato' => $totaleVersato
        ]);
        
        return $totaleVersato;
    }

    /**
     * Ricalcola i bollettini senza modificare quelli già pagati o caricati manualmente
     */
    public function recalculateWithoutChangingPaid(Company $company, int $year): array
    {
        $this->company = $company;
        $this->year = $year;
        $this->taxRecords = [];

        Log::info("🔄 Ricalcolo intelligente bollettini per company dopo import F24", [
            'company_id' => $company->id,
            'year' => $year
        ]);

        // Verifica che la company sia in regime forfettario
        if (!$company->isRegimeForfettario()) {
            throw new \Exception("La company {$company->name} non è in regime forfettario RF19");
        }

        // Calcola il fatturato dinamicamente dalle fatture
        $totalRevenue = $this->calculateRevenueForYear($company, $year - 1);
        
        // Verifica che ci sia un fatturato
        if ($totalRevenue <= 0) {
            Log::warning("Company {$company->name} ha fatturato zero o negativo, skip ricalcolo tasse");
            return [];
        }
        
        // Imposta il total_revenue per il calcolo (non salvato nel DB)
        $company->total_revenue = $totalRevenue;

        DB::beginTransaction();
        
        try {
            // Recupera lo storico
            $storico = $this->recuperaStorico();
            
            // Calcola prima i contributi INPS (per poterli dedurre)
            $contributiInps = $this->calcolaContributiInps($storico);
            
            // Calcola imposta sostitutiva (deducendo i contributi INPS)
            $this->calcolaImpostaSostitutiva($storico, $contributiInps);
            
            // Calcola diritto annuale CCIAA (se iscritto)
            $this->calcolaDirittoAnnualeCCIAA();
            
            // Cancella solo i record pending automatici (NON quelli manuali e NON quelli pagati)
            $this->cancellaRecordPendingWithoutChangingPaid();
            
            // Salva i nuovi record, evitando duplicati con quelli manuali
            $this->salvaRecordTasse();
            
            DB::commit();
            
            // Log dell'operazione
            $this->logOperazione();
            
            Log::info("✅ Ricalcolo intelligente completato", [
                'company_id' => $company->id,
                'bollettini_generati' => count($this->taxRecords),
                'bollettini_paid_preservati' => $company->taxes()
                    ->where('payment_year', $year)
                    ->where('payment_status', Tax::STATUS_PAID)
                    ->count(),
                'bollettini_manuali_preservati' => $company->taxes()
                    ->where('payment_year', $year)
                    ->where('is_manual', true)
                    ->count()
            ]);

            return $this->taxRecords;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Errore ricalcolo tasse per company {$company->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
