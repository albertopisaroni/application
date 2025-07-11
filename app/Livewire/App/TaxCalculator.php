<?php

namespace App\Livewire\App;

use Livewire\Component;
use App\Models\Invoice;
use App\Models\Company;
use Carbon\Carbon;

class TaxCalculator extends Component
{
    public $company;
    public $currentYear;
    public $yearlyInvoices = [];
    public $taxCalculations = [];
    public $selectedYear;

    public function mount()
    {
        $this->company = Company::find(session('current_company_id'));
        $this->currentYear = Carbon::now()->year;
        $this->selectedYear = $this->currentYear;
        $this->calculateTaxes();
    }

    public function updatedSelectedYear()
    {
        $this->calculateTaxes();
    }

    public function calculateTaxes()
    {
        $year = $this->selectedYear;
        
        // Recupera tutte le fatture dell'anno
        $invoices = Invoice::where('company_id', $this->company->id)
            ->whereYear('issue_date', $year)
            ->get();

        $this->yearlyInvoices = $invoices;

        // Calcola i totali
        $totalRevenue = $invoices->sum('total');
        $totalVat = $invoices->sum('vat');

        // Calcola le tasse basandosi sul regime fiscale dell'azienda
        $this->taxCalculations = $this->calculateTaxesForCompany($totalRevenue, $year);
    }

    private function calculateTaxesForCompany($totalRevenue, $year)
    {
        $company = $this->company;
        $coefficient = $company->coefficiente / 100; // Es. 67% = 0.67
        $isStartup = $company->startup;
        $gestioneSeparata = $company->gestione_separata;

        // Imponibile forfettario
        $imponibileForfettario = round($totalRevenue * $coefficient, 2);

        // Bollo (se fatturato > 77€)
        $bollo = $totalRevenue > 77 ? 2 : 0;

        // Contributi fissi annuali
        $contributiFissi = $gestioneSeparata ? 0 : 4200;

        if ($gestioneSeparata) {
            // Gestione separata
            $inpsPercentuale = round($imponibileForfettario * 0.2607, 2);
            $inps = $inpsPercentuale;
            $aliquotaImposta = $isStartup ? 0.05 : 0.15;
            $irpef = round($imponibileForfettario * $aliquotaImposta, 2);
        } else {
            // Artigiani / Commercianti
            $inpsPercentuale = round($imponibileForfettario * 0.24, 2);
            $inps = $inpsPercentuale + $contributiFissi;
            $imponibileNettoInps = $imponibileForfettario - $inpsPercentuale;
            $aliquotaImposta = $isStartup ? 0.05 : 0.15;
            $irpef = round($imponibileNettoInps * $aliquotaImposta, 2);
        }

        // Totale tasse
        $totalTaxes = $inps + $irpef + $bollo;

        // Netto post tasse
        $nettoPostTasse = $totalRevenue - $totalTaxes;

        return [
            'total_revenue' => $totalRevenue,
            'imponibile_forfettario' => $imponibileForfettario,
            'coefficient' => $coefficient * 100,
            'bollo' => $bollo,
            'contributi_fissi' => $contributiFissi,
            'inps_percentuale' => $inpsPercentuale,
            'inps_totale' => $inps,
            'irpef' => $irpef,
            'aliquota_irpef' => $aliquotaImposta * 100,
            'total_taxes' => $totalTaxes,
            'netto_post_tasse' => $nettoPostTasse,
            'regime' => $gestioneSeparata ? 'Gestione Separata' : 'Artigiani/Commercianti',
            'startup' => $isStartup,
        ];
    }

    public function getScadenzeProperty()
    {
        $year = $this->selectedYear;
        $scadenze = [];

        // IVA (se applicabile)
        if ($this->yearlyInvoices->sum('vat') > 0) {
            $scadenze[] = [
                'tipo' => 'IVA',
                'importo' => $this->yearlyInvoices->sum('vat'),
                'scadenza' => Carbon::create($year, 3, 16)->format('d/m/Y'),
                'note' => 'Primo trimestre'
            ];
            $scadenze[] = [
                'tipo' => 'IVA',
                'importo' => $this->yearlyInvoices->sum('vat'),
                'scadenza' => Carbon::create($year, 5, 16)->format('d/m/Y'),
                'note' => 'Secondo trimestre'
            ];
            $scadenze[] = [
                'tipo' => 'IVA',
                'importo' => $this->yearlyInvoices->sum('vat'),
                'scadenza' => Carbon::create($year, 8, 16)->format('d/m/Y'),
                'note' => 'Terzo trimestre'
            ];
            $scadenze[] = [
                'tipo' => 'IVA',
                'importo' => $this->yearlyInvoices->sum('vat'),
                'scadenza' => Carbon::create($year, 11, 30)->format('d/m/Y'),
                'note' => 'Quarto trimestre'
            ];
        }

        // INPS
        $scadenze[] = [
            'tipo' => 'INPS',
            'importo' => $this->taxCalculations['inps_totale'] / 4,
            'scadenza' => Carbon::create($year, 3, 16)->format('d/m/Y'),
            'note' => 'Primo trimestre'
        ];
        $scadenze[] = [
            'tipo' => 'INPS',
            'importo' => $this->taxCalculations['inps_totale'] / 4,
            'scadenza' => Carbon::create($year, 5, 16)->format('d/m/Y'),
            'note' => 'Secondo trimestre'
        ];
        $scadenze[] = [
            'tipo' => 'INPS',
            'importo' => $this->taxCalculations['inps_totale'] / 4,
            'scadenza' => Carbon::create($year, 8, 16)->format('d/m/Y'),
            'note' => 'Terzo trimestre'
        ];
        $scadenze[] = [
            'tipo' => 'INPS',
            'importo' => $this->taxCalculations['inps_totale'] / 4,
            'scadenza' => Carbon::create($year, 11, 30)->format('d/m/Y'),
            'note' => 'Quarto trimestre'
        ];

        // IRPEF (acconto)
        $scadenze[] = [
            'tipo' => 'IRPEF Acconto',
            'importo' => $this->taxCalculations['irpef'] * 0.4,
            'scadenza' => Carbon::create($year, 6, 30)->format('d/m/Y'),
            'note' => 'Primo acconto'
        ];
        $scadenze[] = [
            'tipo' => 'IRPEF Acconto',
            'importo' => $this->taxCalculations['irpef'] * 0.4,
            'scadenza' => Carbon::create($year, 11, 30)->format('d/m/Y'),
            'note' => 'Secondo acconto'
        ];

        // IRPEF (saldo)
        $scadenze[] = [
            'tipo' => 'IRPEF Saldo',
            'importo' => $this->taxCalculations['irpef'] * 0.2,
            'scadenza' => Carbon::create($year + 1, 6, 30)->format('d/m/Y'),
            'note' => 'Saldo'
        ];

        return collect($scadenze)->sortBy('scadenza');
    }

    public function render()
    {
        return view('livewire.app.tax-calculator')
            ->layout('layouts.app');
    }
}
