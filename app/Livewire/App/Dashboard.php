<?php

namespace App\Livewire\App;

use Livewire\Component;
use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\Carbon;

class Dashboard extends Component
{
    public array $months              = [];
    public array $invoiceTotals       = [];
    public array $invoiceSubtotals    = []; // Nuovo: fatture senza IVA
    public array $subscriptionTotals  = [];
    public array $subscriptionForecast = [];
    public array $invoiceNetTotals = [];
    public array $invoiceNetForecast = [];
    public float $invoiceYearTotal    = 0.0;
    public float $invoiceSubtotalYearTotal = 0.0; // Nuovo: totale anno senza IVA
    public float $subscriptionYearTotal = 0.0;
    public float $subscriptionForecastTotal = 0.0;
    public float $invoiceNetYearTotal = 0.0;
    public float $invoiceNetForecastTotal = 0.0;

    public function mount()
{
    $companyId    = session('current_company_id');
    $now          = Carbon::now();
    $year         = $now->year;
    $currentMonth = $now->month;

    // 1) etichette dei mesi
    $this->months = collect(range(1, 12))
        ->map(fn($m) => Carbon::create()->month($m)->locale('it')->isoFormat('MMM'))
        ->toArray();

    // 2) Totali mensili Fatture (campo `total` su invoices) - Note di credito
    $this->invoiceTotals = collect(range(1, 12))
        ->map(fn($m) => 
            // Fatture ordinarie (escludendo TD04)
            Invoice::where('company_id', $companyId)
                ->whereYear('issue_date', $year)
                ->whereMonth('issue_date', $m)
                ->whereNotIn('document_type', ['TD04'])
                ->sum('total')
            -
            // Note di credito (TD04)
            Invoice::where('company_id', $companyId)
                ->whereYear('issue_date', $year)
                ->whereMonth('issue_date', $m)
                ->where('document_type', 'TD04')
                ->sum('total')
        )
        ->toArray();

    // 2a) Totali mensili Fatture SENZA IVA (subtotal)
    $this->invoiceSubtotals = collect(range(1, 12))
        ->map(fn($m) => 
            // Subtotal fatture ordinarie (escludendo TD04)
            Invoice::where('company_id', $companyId)
                ->whereYear('issue_date', $year)
                ->whereMonth('issue_date', $m)
                ->whereNotIn('document_type', ['TD04'])
                ->sum('subtotal')
            -
            // Subtotal note di credito (TD04)
            Invoice::where('company_id', $companyId)
                ->whereYear('issue_date', $year)
                ->whereMonth('issue_date', $m)
                ->where('document_type', 'TD04')
                ->sum('subtotal')
        )
        ->toArray();

    // 2b) Totale annuale fatture - Note di credito
    $this->invoiceYearTotal = 
        // Fatture ordinarie (escludendo TD04)
        Invoice::where('company_id', $companyId)
            ->whereYear('issue_date', $year)
            ->whereNotIn('document_type', ['TD04'])
            ->sum('total')
        -
        // Note di credito (TD04)
        Invoice::where('company_id', $companyId)
            ->whereYear('issue_date', $year)
            ->where('document_type', 'TD04')
            ->sum('total');

    // 2c) Totale annuale subtotal - Note di credito
    $this->invoiceSubtotalYearTotal = 
        // Subtotal fatture ordinarie (escludendo TD04)
        Invoice::where('company_id', $companyId)
            ->whereYear('issue_date', $year)
            ->whereNotIn('document_type', ['TD04'])
            ->sum('subtotal')
        -
        // Subtotal note di credito (TD04)
        Invoice::where('company_id', $companyId)
            ->whereYear('issue_date', $year)
            ->where('document_type', 'TD04')
            ->sum('subtotal');

    // 2c) Totale incassati per l'anno corrente
    $this->subscriptionYearTotal = Subscription::whereHas('client', fn($q) =>
            $q->where('company_id', $companyId)
        )
        ->whereYear('current_period_start', $year) // Incassati nell'anno corrente
        ->where('status', 'active')
        ->sum('total_with_vat') / 100; // Converti da centesimi a euro

    // 3) Totali mensili INCASSATI (periodi iniziati nell'anno corrente, mese per mese)
    $this->subscriptionTotals = collect(range(1, 12))
        ->map(fn($m) => Subscription::whereHas('client', fn($q) =>
                    $q->where('company_id', $companyId)
                )
                ->whereYear('current_period_start', $year) // Anno corrente
                ->whereMonth('current_period_start', $m)
                ->where('status', 'active') // Subscription attive (incassate)
                ->sum('total_with_vat') / 100 // Converti da centesimi a euro
        )
        ->toArray();

    // 4) Totali mensili PREVISTI (rinnovi di tutti gli anni, solo attive)
    $renewalTotals = collect(range(1, 12))
        ->map(fn($m) => Subscription::whereHas('client', fn($q) =>
                    $q->where('company_id', $companyId)
                )
                ->whereMonth('current_period_end', $m) // Tutti gli anni
                ->whereIn('status', ['active', 'past_due']) // Solo subscription attive o in trial
                ->sum('total_with_vat') / 100 // Converti da centesimi a euro
        )
        ->toArray();

    // 5) Forecast mensile per le sottoscrizioni (sempre pattern di tutti gli anni)
    $this->subscriptionForecast = $renewalTotals;

    // 6) Totale previsioni annuali abbonamenti (calcolato dopo aver popolato l'array)
    $this->subscriptionForecastTotal = array_sum($this->subscriptionForecast);

    // 7) Calcoli per il grafico NETTO (Fatture - IVA - Spese)
    $this->calculateNetTotals($companyId, $year);
}

    /**
     * Calcola i totali netti (Fatture - IVA - Spese Passive)
     */
    protected function calculateNetTotals($companyId, $year)
    {
        // Totali mensili NETTI
        $this->invoiceNetTotals = collect(range(1, 12))
            ->map(function($m) use ($companyId, $year) {
                // Fatture del mese (escludendo note di credito TD04)
                $invoiceTotal = Invoice::where('company_id', $companyId)
                    ->whereYear('issue_date', $year)
                    ->whereMonth('issue_date', $m)
                    ->whereNotIn('document_type', ['TD04'])
                    ->sum('total');

                // Note di credito del mese
                $creditNotes = Invoice::where('company_id', $companyId)
                    ->whereYear('issue_date', $year)
                    ->whereMonth('issue_date', $m)
                    ->where('document_type', 'TD04')
                    ->sum('total');

                // Spese passive del mese
                $passiveExpenses = \App\Models\InvoicePassive::where('company_id', $companyId)
                    ->whereYear('issue_date', $year)
                    ->whereMonth('issue_date', $m)
                    ->sum('total');

                // IVA delle fatture del mese (campo vat delle fatture)
                $invoiceVat = Invoice::where('company_id', $companyId)
                    ->whereYear('issue_date', $year)
                    ->whereMonth('issue_date', $m)
                    ->whereNotIn('document_type', ['TD04'])
                    ->sum('vat');
                
                // IVA delle note di credito (da sottrarre)
                $creditNotesVat = Invoice::where('company_id', $companyId)
                    ->whereYear('issue_date', $year)
                    ->whereMonth('issue_date', $m)
                    ->where('document_type', 'TD04')
                    ->sum('vat');
                
                $totalVat = $invoiceVat - $creditNotesVat;

                // Calcolo netto: Fatture - Note Credito - IVA - Spese
                $netAmount = ($invoiceTotal - $creditNotes) - $totalVat - $passiveExpenses;

                return max(0, $netAmount); // Non valori negativi
            })
            ->toArray();

        // Totale anno netto
        $this->invoiceNetYearTotal = array_sum($this->invoiceNetTotals);

        // Forecast netto (per ora uguale ai totali, si può migliorare)
        $this->invoiceNetForecast = $this->invoiceNetTotals;
        $this->invoiceNetForecastTotal = $this->invoiceNetYearTotal;
    }

    public function render()
    {
        return view('livewire.app.dashboard')
            ->layout('layouts.app');
    }
}