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

    // Proprietà per il selettore di date
    public string $dateRange = '12M'; // Default: 12 mesi (anno corrente)
    public ?string $customStartDate = null;
    public ?string $customEndDate = null;
    public bool $showCustomDatePicker = false;

    public function mount()
    {
        $this->loadData();
    }



    public function updatedCustomStartDate()
    {
        if ($this->customStartDate && $this->customEndDate) {
            if ($this->validateCustomDateRange()) {
                $this->loadData();
                $this->dispatch('charts-updated');
            }
        }
    }

    public function updatedCustomEndDate()
    {
        if ($this->customStartDate && $this->customEndDate) {
            if ($this->validateCustomDateRange()) {
                $this->loadData();
                $this->dispatch('charts-updated');
            }
        }
    }

    public function setCustomRange()
    {
        $this->dateRange = 'custom';
        $this->showCustomDatePicker = true;
        
        // Se non ci sono date personalizzate, usa un periodo di default (ultimi 3 mesi)
        if (!$this->customStartDate || !$this->customEndDate) {
            $now = Carbon::now();
            $this->customStartDate = $now->copy()->subMonths(2)->startOfMonth()->format('Y-m-d');
            $this->customEndDate = $now->copy()->endOfMonth()->format('Y-m-d');
        }
        
        $this->loadData();
        $this->dispatch('charts-updated');
    }

    public function setDateRange($range)
    {
        $this->dateRange = $range;
        $this->showCustomDatePicker = false;
        $this->customStartDate = null;
        $this->customEndDate = null;
        $this->loadData();
        
        // Dispatch event per aggiornare i grafici
        $this->dispatch('charts-updated');
    }

    public function hideCustomDatePicker()
    {
        $this->showCustomDatePicker = false;
    }

    private function validateCustomDateRange()
    {
        try {
            $startDate = Carbon::parse($this->customStartDate);
            $endDate = Carbon::parse($this->customEndDate);
            
            // Verifica che la data di fine sia successiva alla data di inizio
            if ($endDate->lt($startDate)) {
                $this->addError('customEndDate', 'La data di fine deve essere successiva alla data di inizio.');
                return false;
            }
            
            // Verifica che il periodo sia di almeno 1 mese
            $diffInMonths = $startDate->diffInMonths($endDate);
            if ($diffInMonths < 1) {
                $this->addError('customEndDate', 'Il periodo deve essere di almeno 1 mese.');
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            $this->addError('customEndDate', 'Date non valide.');
            return false;
        }
    }

    private function getDateRange()
    {
        $now = Carbon::now();
        
        switch ($this->dateRange) {
            case '1M':
                // Mese corrente
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                    'granularity' => 'weekly'
                ];
            case '3M':
                // Ultimi 3 mesi (ultimo è il mese corrente)
                return [
                    'start' => $now->copy()->subMonths(2)->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                    'granularity' => 'weekly'
                ];
            case '6M':
                // Ultimi 6 mesi (ultimo è il mese corrente)
                return [
                    'start' => $now->copy()->subMonths(5)->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                    'granularity' => 'monthly'
                ];
            case '12M':
                // Anno corrente
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfYear(),
                    'granularity' => 'monthly'
                ];
            case 'custom':
                if ($this->customStartDate && $this->customEndDate) {
                    try {
                        $startDate = Carbon::parse($this->customStartDate);
                        $endDate = Carbon::parse($this->customEndDate);
                        $diffInMonths = $startDate->diffInMonths($endDate);
                        
                        return [
                            'start' => $startDate,
                            'end' => $endDate,
                            'granularity' => $diffInMonths <= 3 ? 'weekly' : 'monthly'
                        ];
                    } catch (\Exception $e) {
                        // Fallback a ultimi 3 mesi se le date non sono valide
                        return [
                            'start' => $now->copy()->subMonths(2)->startOfMonth(),
                            'end' => $now->copy()->endOfMonth(),
                            'granularity' => 'weekly'
                        ];
                    }
                }
                // Fallback a ultimi 3 mesi se custom non è valido
                return [
                    'start' => $now->copy()->subMonths(2)->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                    'granularity' => 'weekly'
                ];
            default:
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfYear(),
                    'granularity' => 'monthly'
                ];
        }
    }

    private function generateLabels($startDate, $endDate, $granularity)
    {
        $labels = [];
        $current = $startDate->copy();
        
        if ($granularity === 'weekly') {
            while ($current->lte($endDate)) {
                $labels[] = $current->format('d/m/Y');
                $current->addDays(7);
            }
        } else {
            while ($current->lte($endDate)) {
                $labels[] = $current->locale('it')->isoFormat('MMM YYYY');
                $current->addMonth();
            }
        }
        
        return $labels;
    }

    public function loadData()
    {
        $companyId = session('current_company_id');
        
        if (!$companyId) {
            // Se non c'è una company selezionata, usa dati vuoti
            $this->months = [];
            $this->invoiceTotals = [];
            $this->invoiceSubtotals = [];
            $this->subscriptionTotals = [];
            $this->subscriptionForecast = [];
            $this->invoiceNetTotals = [];
            $this->invoiceNetForecast = [];
            $this->invoiceYearTotal = 0.0;
            $this->invoiceSubtotalYearTotal = 0.0;
            $this->subscriptionYearTotal = 0.0;
            $this->subscriptionForecastTotal = 0.0;
            $this->invoiceNetYearTotal = 0.0;
            $this->invoiceNetForecastTotal = 0.0;
            return;
        }
        
        $dateRange = $this->getDateRange();
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        $granularity = $dateRange['granularity'];
        
        // Genera le etichette
        $this->months = $this->generateLabels($startDate, $endDate, $granularity);
        
        // Calcola i dati in base alla granularità
        if ($granularity === 'weekly') {
            $this->calculateWeeklyData($companyId, $startDate, $endDate);
        } else {
            $this->calculateMonthlyData($companyId, $startDate, $endDate);
        }
    }

    private function calculateWeeklyData($companyId, $startDate, $endDate)
    {
        $this->invoiceTotals = [];
        $this->invoiceSubtotals = [];
        $this->subscriptionTotals = [];
        
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            $weekStart = $current->copy()->startOfWeek();
            $weekEnd = $current->copy()->endOfWeek();
            
            // Fatture della settimana
            $invoiceTotal = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$weekStart, $weekEnd])
                ->whereNotIn('document_type', ['TD04'])
                ->sum('total');
            
            $creditNotes = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$weekStart, $weekEnd])
                ->where('document_type', 'TD04')
                ->sum('total');
            
            $this->invoiceTotals[] = $invoiceTotal - $creditNotes;
            
            // Subtotal fatture
            $invoiceSubtotal = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$weekStart, $weekEnd])
                ->whereNotIn('document_type', ['TD04'])
                ->sum('subtotal');
            
            $creditNotesSubtotal = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$weekStart, $weekEnd])
                ->where('document_type', 'TD04')
                ->sum('subtotal');
            
            $this->invoiceSubtotals[] = $invoiceSubtotal - $creditNotesSubtotal;
            
            // Abbonamenti della settimana
            $subscriptionTotal = Subscription::whereHas('client', fn($q) =>
                    $q->where('company_id', $companyId)
                )
                ->whereBetween('current_period_start', [$weekStart, $weekEnd])
                ->where('status', 'active')
                ->sum('total_with_vat') / 100;
            
            $this->subscriptionTotals[] = $subscriptionTotal;
            
            $current->addDays(7);
        }
        
        // Calcola i totali
        $this->invoiceYearTotal = array_sum($this->invoiceTotals);
        $this->invoiceSubtotalYearTotal = array_sum($this->invoiceSubtotals);
        $this->subscriptionYearTotal = array_sum($this->subscriptionTotals);
        
        // Per ora, forecast uguale ai totali (si può migliorare)
        $this->subscriptionForecast = $this->subscriptionTotals;
        $this->subscriptionForecastTotal = $this->subscriptionYearTotal;
        
        // Calcola i dati netti
        $this->calculateNetTotalsWeekly($companyId, $startDate, $endDate);
    }

    private function calculateMonthlyData($companyId, $startDate, $endDate)
    {
        $this->invoiceTotals = [];
        $this->invoiceSubtotals = [];
        $this->subscriptionTotals = [];
        $this->subscriptionForecast = [];
        
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();
            
            // Fatture del mese
            $invoiceTotal = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$monthStart, $monthEnd])
                ->whereNotIn('document_type', ['TD04'])
                ->sum('total');
            
            $creditNotes = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$monthStart, $monthEnd])
                ->where('document_type', 'TD04')
                ->sum('total');
            
            $this->invoiceTotals[] = $invoiceTotal - $creditNotes;
            
            // Subtotal fatture
            $invoiceSubtotal = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$monthStart, $monthEnd])
                ->whereNotIn('document_type', ['TD04'])
                ->sum('subtotal');
            
            $creditNotesSubtotal = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$monthStart, $monthEnd])
                ->where('document_type', 'TD04')
                ->sum('subtotal');
            
            $this->invoiceSubtotals[] = $invoiceSubtotal - $creditNotesSubtotal;
            
            // Abbonamenti del mese
            $subscriptionTotal = Subscription::whereHas('client', fn($q) =>
                    $q->where('company_id', $companyId)
                )
                ->whereBetween('current_period_start', [$monthStart, $monthEnd])
                ->where('status', 'active')
                ->sum('total_with_vat') / 100;
            
            $this->subscriptionTotals[] = $subscriptionTotal;
            
            $current->addMonth();
        }
        
        // Calcola i totali
        $this->invoiceYearTotal = array_sum($this->invoiceTotals);
        $this->invoiceSubtotalYearTotal = array_sum($this->invoiceSubtotals);
        $this->subscriptionYearTotal = array_sum($this->subscriptionTotals);
        
        // Forecast mensile per le sottoscrizioni
        $this->calculateSubscriptionForecast($companyId, $startDate, $endDate);
        
        // Calcola i dati netti
        $this->calculateNetTotals($companyId, $startDate, $endDate);
    }

    private function calculateSubscriptionForecast($companyId, $startDate, $endDate)
    {
        $this->subscriptionForecast = [];
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();
            
            // Previsioni per il mese (rinnovi di tutti gli anni, solo attive)
            $renewalTotal = Subscription::whereHas('client', fn($q) =>
                    $q->where('company_id', $companyId)
                )
                ->whereMonth('current_period_end', $current->month)
                ->whereIn('status', ['active', 'past_due'])
                ->sum('total_with_vat') / 100;
            
            $this->subscriptionForecast[] = $renewalTotal;
            
            $current->addMonth();
        }
        
        $this->subscriptionForecastTotal = array_sum($this->subscriptionForecast);
    }

    private function calculateNetTotalsWeekly($companyId, $startDate, $endDate)
    {
        $this->invoiceNetTotals = [];
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            $weekStart = $current->copy()->startOfWeek();
            $weekEnd = $current->copy()->endOfWeek();
            
            // Fatture della settimana
            $invoiceTotal = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$weekStart, $weekEnd])
                ->whereNotIn('document_type', ['TD04'])
                ->sum('total');
            
            $creditNotes = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$weekStart, $weekEnd])
                ->where('document_type', 'TD04')
                ->sum('total');
            
            // Spese passive della settimana
            $passiveExpenses = \App\Models\InvoicePassive::where('company_id', $companyId)
                ->whereBetween('issue_date', [$weekStart, $weekEnd])
                ->sum('total');
            
            // IVA delle fatture della settimana
            $invoiceVat = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$weekStart, $weekEnd])
                ->whereNotIn('document_type', ['TD04'])
                ->sum('vat');
            
            $creditNotesVat = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$weekStart, $weekEnd])
                ->where('document_type', 'TD04')
                ->sum('vat');
            
            $totalVat = $invoiceVat - $creditNotesVat;
            
            // Calcolo netto
            $netAmount = ($invoiceTotal - $creditNotes) - $totalVat - $passiveExpenses;
            $this->invoiceNetTotals[] = $netAmount; // Può essere negativo
            
            $current->addDays(7);
        }
        
        $this->invoiceNetYearTotal = array_sum($this->invoiceNetTotals);
        $this->invoiceNetForecast = $this->invoiceNetTotals;
        $this->invoiceNetForecastTotal = $this->invoiceNetYearTotal;
    }

    /**
     * Calcola i totali netti (Fatture - IVA - Spese Passive)
     */
    protected function calculateNetTotals($companyId, $startDate, $endDate)
    {
        $this->invoiceNetTotals = [];
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();
            
            // Fatture del mese
            $invoiceTotal = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$monthStart, $monthEnd])
                ->whereNotIn('document_type', ['TD04'])
                ->sum('total');
            
            $creditNotes = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$monthStart, $monthEnd])
                ->where('document_type', 'TD04')
                ->sum('total');
            
            // Spese passive del mese
            $passiveExpenses = \App\Models\InvoicePassive::where('company_id', $companyId)
                ->whereBetween('issue_date', [$monthStart, $monthEnd])
                ->sum('total');
            
            // IVA delle fatture del mese
            $invoiceVat = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$monthStart, $monthEnd])
                ->whereNotIn('document_type', ['TD04'])
                ->sum('vat');
            
            $creditNotesVat = Invoice::where('company_id', $companyId)
                ->whereBetween('issue_date', [$monthStart, $monthEnd])
                ->where('document_type', 'TD04')
                ->sum('vat');
            
            $totalVat = $invoiceVat - $creditNotesVat;
            
            // Calcolo netto
            $netAmount = ($invoiceTotal - $creditNotes) - $totalVat - $passiveExpenses;
            $this->invoiceNetTotals[] = $netAmount; // Può essere negativo
            
            $current->addMonth();
        }
        
        $this->invoiceNetYearTotal = array_sum($this->invoiceNetTotals);
        $this->invoiceNetForecast = $this->invoiceNetTotals;
        $this->invoiceNetForecastTotal = $this->invoiceNetYearTotal;
    }

    public function render()
    {
        return view('livewire.app.dashboard')
            ->layout('layouts.app');
    }
}