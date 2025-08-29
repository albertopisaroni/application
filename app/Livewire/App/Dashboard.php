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
    public array $subscriptionTotals  = [];
    public array $subscriptionForecast = [];
    public float $invoiceYearTotal    = 0.0;
    public float $subscriptionYearTotal = 0.0;
    public float $subscriptionForecastTotal = 0.0;

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
}

    public function render()
    {
        return view('livewire.app.dashboard')
            ->layout('layouts.app');
    }
}