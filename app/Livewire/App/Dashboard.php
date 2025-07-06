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

    // 2) Totali mensili Fatture (campo `total` su invoices)
    $this->invoiceTotals = collect(range(1, 12))
        ->map(fn($m) => Invoice::where('company_id', $companyId)
                ->whereYear('issue_date', $year)
                ->whereMonth('issue_date', $m)
                ->sum('total')
        )
        ->toArray();

    // 3) Totali mensili reali Abbonamenti (nuovi)
    $this->subscriptionTotals = collect(range(1, 12))
        ->map(fn($m) => Subscription::whereHas('client', fn($q) =>
                    $q->where('company_id', $companyId)
                )
                ->whereYear('start_date', $year)
                ->whereMonth('start_date', $m)
                ->sum('final_amount')
        )
        ->toArray();

    // 4) Totali mensili reali Rinnovi (fine periodo)
    $renewalTotals = collect(range(1, 12))
        ->map(fn($m) => Subscription::whereHas('client', fn($q) =>
                    $q->where('company_id', $companyId)
                )
                ->whereYear('current_period_end', $year)
                ->whereMonth('current_period_end', $m)
                ->sum('final_amount')
        )
        ->toArray();

    // 5) Forecast mensile per le sottoscrizioni
    $this->subscriptionForecast = collect(range(1, 12))
        ->map(fn($m) => 
            // fino al mese corrente: reale (nuovi + rinnovi)
            $m <= $currentMonth
                ? ($this->subscriptionTotals[$m - 1] + $renewalTotals[$m - 1])
                // mesi futuri: somma programmata di nuovi + rinnovi
                : (
                    Subscription::whereHas('client', fn($q) => 
                        $q->where('company_id', $companyId)
                    )
                    ->whereYear('start_date', $year)
                    ->whereMonth('start_date', $m)
                    ->sum('final_amount')
                    +
                    Subscription::whereHas('client', fn($q) => 
                        $q->where('company_id', $companyId)
                    )
                    ->whereYear('current_period_end', $year)
                    ->whereMonth('current_period_end', $m)
                    ->sum('final_amount')
                )
        )
        ->toArray();
}

    public function render()
    {
        return view('livewire.app.dashboard')
            ->layout('layouts.app');
    }
}