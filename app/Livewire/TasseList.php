<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tax;
use Carbon\Carbon;

class TasseList extends Component
{
    use WithPagination;

    public array $years = [];
    public $yearFilter = null;
    public $perPage = 10;
    public $search = '';
    public $paymentStatusFilter = null;
    public $taxTypeFilter = null;
    public $viewMode = 'taxes'; // 'taxes' or 'f24'
    public $filteredByF24 = null; // ID dell'F24 per filtrare le tasse
    public $filteredByTax = null; // ID della tassa per filtrare gli F24

    protected $queryString = [
        'yearFilter' => ['except' => null],
        'search' => ['except' => ''],
        'paymentStatusFilter' => ['except' => null],
        'taxTypeFilter' => ['except' => null],
        'viewMode' => ['except' => 'taxes'],
        'filteredByF24' => ['except' => null],
        'filteredByTax' => ['except' => null],
    ];

    public function updatingYearFilter()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->yearFilter = null;
        $this->search = '';
        $this->paymentStatusFilter = null;
        $this->taxTypeFilter = null;
        $this->filteredByF24 = null;
        $this->filteredByTax = null;
        $this->resetPage();
    }

    public function switchViewMode($mode)
    {
        $this->viewMode = $mode;
        $this->resetPage();
    }

    public function showTaxesForF24($f24Id)
    {
        $this->viewMode = 'taxes';
        $this->filteredByF24 = $f24Id;
        $this->filteredByTax = null;
        $this->resetPage();
    }

    public function showF24ForTax($f24Id)
    {
        $this->viewMode = 'f24';
        $this->filteredByTax = $f24Id;
        $this->filteredByF24 = null;
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filteredByF24 = null;
        $this->filteredByTax = null;
        $this->resetPage();
    }

    public function markAsPaid($taxId, $paymentData = null)
    {
        try {
            $tax = Tax::findOrFail($taxId);
            
            $paymentDate = $paymentData['paymentDate'] ?? now()->format('Y-m-d');
            $reference = $paymentData['reference'] ?? null;
            
            $tax->markAsPaid($paymentDate, $reference);
            
            $this->dispatch('show-success', message: 'Tassa marcata come pagata');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Errore nella registrazione del pagamento: ' . $e->getMessage());
        }
    }

    public function cancelTax($taxId)
    {
        try {
            $tax = Tax::findOrFail($taxId);
            $tax->cancel();
            
            $this->dispatch('show-success', message: 'Tassa annullata');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Errore nell\'annullamento: ' . $e->getMessage());
        }
    }

    public function markF24AsPaid($f24Id, $paymentData = null)
    {
        try {
            \Log::info('markF24AsPaid chiamato', ['f24Id' => $f24Id, 'paymentData' => $paymentData]);
            
            $f24 = \App\Models\F24::findOrFail($f24Id);
            
            // Verifica che l'utente abbia i permessi
            if ($f24->company_id !== session('current_company_id')) {
                throw new \Exception('Non hai i permessi per modificare questo F24');
            }
            
            \Log::info('F24 trovato', ['f24_id' => $f24->id, 'current_status' => $f24->payment_status]);
            
            $paidDate = $paymentData['paymentDate'] ?? now()->format('Y-m-d');
            $reference = $paymentData['reference'] ?? null;
            
            $f24->markAsPaid($paidDate, $reference);
            
            \Log::info('F24 marcato come pagato', ['f24_id' => $f24->id, 'new_status' => $f24->payment_status]);
            
            $this->dispatch('show-success', message: 'F24 marcato come pagato');
        } catch (\Exception $e) {
            \Log::error('Errore markF24AsPaid', ['error' => $e->getMessage(), 'f24Id' => $f24Id]);
            $this->dispatch('show-error', message: 'Errore nella registrazione del pagamento: ' . $e->getMessage());
        }
    }

    public function uploadF24Receipt($f24Id, $fileData)
    {
        try {
            $f24 = \App\Models\F24::findOrFail($f24Id);
            
            // Verifica che l'utente abbia i permessi
            if ($f24->company_id !== session('current_company_id')) {
                throw new \Exception('Non hai i permessi per modificare questo F24');
            }
            
            // Decodifica il file base64
            $fileContent = base64_decode($fileData['content']);
            $filename = $fileData['name'];
            
            // Crea un file temporaneo
            $tempPath = tempnam(sys_get_temp_dir(), 'receipt_');
            file_put_contents($tempPath, $fileContent);
            
            // Crea un oggetto file per l'upload
            $file = new \Illuminate\Http\UploadedFile($tempPath, $filename);
            
            if ($f24->uploadReceipt($file)) {
                $this->dispatch('show-success', message: 'Ricevuta caricata con successo');
                return [
                    'success' => true,
                    'receipt_filename' => $f24->receipt_filename,
                    'receipt_uploaded_at' => $f24->receipt_uploaded_at->format('d/m/Y H:i'),
                ];
            } else {
                throw new \Exception('Errore durante il caricamento della ricevuta');
            }
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Errore nel caricamento della ricevuta: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteF24Receipt($f24Id)
    {
        try {
            $f24 = \App\Models\F24::findOrFail($f24Id);
            
            // Verifica che l'utente abbia i permessi
            if ($f24->company_id !== session('current_company_id')) {
                throw new \Exception('Non hai i permessi per modificare questo F24');
            }
            
            if ($f24->deleteReceipt()) {
                $this->dispatch('show-success', message: 'Ricevuta eliminata con successo');
                return true;
            } else {
                throw new \Exception('Errore durante l\'eliminazione della ricevuta');
            }
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Errore nell\'eliminazione della ricevuta: ' . $e->getMessage());
            return false;
        }
    }

    public function importF24($f24Data)
    {
        try {
            \Log::info("Inizio importazione F24", [
                'data_received' => array_keys($f24Data),
                'files_count' => count($f24Data['files'] ?? [])
            ]);

            // CRITICO: Non fare nulla se non ci sono file!
            $filesCount = count($f24Data['files'] ?? []);
            if ($filesCount === 0) {
                \Log::info("Nessun file da importare - operazione annullata");
                $this->dispatch('show-warning', message: 'Nessun file selezionato per l\'importazione');
                return;
            }

            $currentCompanyId = session('current_company_id');
            if (!$currentCompanyId) {
                throw new \Exception('Nessuna company selezionata in sessione');
            }

            $company = \App\Models\Company::findOrFail($currentCompanyId);
            \Log::info("Company trovata", ['company_id' => $company->id, 'company_name' => $company->name]);
            
            // Usa il servizio F24 per parsare e importare
            $f24Service = new \App\Services\F24ImportService();
            $result = $f24Service->importF24Files($company, $f24Data);
            
            \Log::info("Importazione F24 completata", $result);
            
            // Ricalcola solo se l'utente ha selezionato l'opzione E ci sono stati import effettivi
            if (($f24Data['auto_recalculate'] ?? false) && ($result['imported'] > 0)) {
                \Log::info("Ricalcolo automatico richiesto");
                $taxService = new \App\Services\TaxCalculationService();
                $taxService->recalculateWithoutChangingPaid($company, now()->year);
                
                $skipDuplicates = $f24Data['skip_duplicates'] ?? false;
                $duplicateMessage = $skipDuplicates && $result['skipped'] > 0 ? " ({$result['skipped']} duplicati saltati)" : "";
                
                $this->dispatch('show-success', message: "F24 importati con successo: {$result['imported']} righe{$duplicateMessage}. Bollettini ricalcolati automaticamente.");
            } else {
                \Log::info("Ricalcolo automatico non richiesto");
                
                $skipDuplicates = $f24Data['skip_duplicates'] ?? false;
                $duplicateMessage = $skipDuplicates && $result['skipped'] > 0 ? " ({$result['skipped']} duplicati saltati)" : "";
                
                $this->dispatch('show-success', message: "F24 importati con successo: {$result['imported']} righe{$duplicateMessage}. Per aggiornare i bollettini, esegui il comando di calcolo tasse.");
            }
            
            // Refresh della pagina
            $this->render();
            
        } catch (\Exception $e) {
            \Log::error("Errore importazione F24", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('show-error', message: 'Errore nell\'importazione F24: ' . $e->getMessage());
        }
    }

    public function mount()
    {
        if ($this->yearFilter === '') {
            $this->yearFilter = null;
        }
    
        $currentCompanyId = session('current_company_id');
        
        $this->years = Tax::where('company_id', $currentCompanyId)
            ->selectRaw('DISTINCT payment_year')
            ->orderByDesc('payment_year')
            ->pluck('payment_year')
            ->toArray();
    }

    public function render()
    {
        $currentCompanyId = session('current_company_id');

        if ($this->viewMode === 'f24') {
            return $this->renderF24View($currentCompanyId);
        } else {
            return $this->renderTaxesView($currentCompanyId);
        }
    }

    private function renderTaxesView($currentCompanyId)
    {
        $query = Tax::with(['company', 'f24'])
            ->where('company_id', $currentCompanyId)
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'desc');

        $allTaxes = $query->get();

        // Filtri
        if ($this->yearFilter) {
            $query->where('payment_year', $this->yearFilter);
        }

        if ($this->taxTypeFilter) {
            $query->where('tax_type', $this->taxTypeFilter);
        }

        // Filtro per F24 specifico
        if ($this->filteredByF24) {
            $query->where('f24_id', $this->filteredByF24);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('tax_code', 'like', '%' . $this->search . '%')
                  ->orWhere('tax_type', 'like', '%' . $this->search . '%');
            });
        }      

        if ($this->paymentStatusFilter === 'unpaid') {
            $query->whereIn('payment_status', [Tax::STATUS_PENDING, Tax::STATUS_OVERDUE]);
        } elseif ($this->paymentStatusFilter === 'paid') {
            $query->where('payment_status', Tax::STATUS_PAID);
        }

        $taxes = $query->paginate($this->perPage);

        // Statistiche
        $pendingTaxes = $allTaxes->whereIn('payment_status', [Tax::STATUS_PENDING, Tax::STATUS_OVERDUE]);
        $paidTaxes = $allTaxes->where('payment_status', Tax::STATUS_PAID);
        
        $unpaidCount = $pendingTaxes->count();
        $paidCount = $paidTaxes->count();
        $unpaidTotal = $pendingTaxes->sum('amount');

        // Tipi di tasse disponibili
        $taxTypes = Tax::where('company_id', $currentCompanyId)
            ->select('tax_type')
            ->distinct()
            ->orderBy('tax_type')
            ->pluck('tax_type');

        // Ottieni l'F24 se stiamo filtrando per esso
        $filteredF24 = null;
        if ($this->filteredByF24) {
            $filteredF24 = \App\Models\F24::find($this->filteredByF24);
        }

        return view('livewire.tasse-list', [
            'taxes' => $taxes,
            'unpaidCount' => $unpaidCount,
            'paidCount' => $paidCount,
            'unpaidTotal' => $unpaidTotal,
            'taxTypes' => $taxTypes,
            'filteredF24' => $filteredF24,
        ]);
    }

    private function renderF24View($currentCompanyId)
    {
        $query = \App\Models\F24::with(['taxes'])
            ->where('company_id', $currentCompanyId)
            ->orderBy('imported_at', 'desc');

        // Filtri
        if ($this->yearFilter) {
            $query->whereJsonContains('reference_years', $this->yearFilter);
        }

        // Filtro per F24 specifico (quando si arriva da una tassa)
        if ($this->filteredByTax) {
            $query->where('id', $this->filteredByTax);
        }

        if ($this->paymentStatusFilter === 'unpaid') {
            $query->whereIn('payment_status', [\App\Models\F24::STATUS_PENDING, \App\Models\F24::STATUS_PARTIALLY_PAID]);
        } elseif ($this->paymentStatusFilter === 'paid') {
            $query->where('payment_status', \App\Models\F24::STATUS_PAID);
        }

        if ($this->search) {
            $query->where('filename', 'like', '%' . $this->search . '%');
        }

        $f24s = $query->paginate($this->perPage);

        // Statistiche
        $allF24s = \App\Models\F24::where('company_id', $currentCompanyId)->get();
        $pendingF24s = $allF24s->whereIn('payment_status', [\App\Models\F24::STATUS_PENDING, \App\Models\F24::STATUS_PARTIALLY_PAID]);
        $paidF24s = $allF24s->where('payment_status', \App\Models\F24::STATUS_PAID);
        
        $unpaidCount = $pendingF24s->count();
        $paidCount = $paidF24s->count();
        $unpaidTotal = $pendingF24s->sum('total_amount');

        // Ottieni l'F24 se stiamo filtrando per esso (quando si arriva da una tassa)
        $filteredF24 = null;
        if ($this->filteredByTax) {
            $filteredF24 = \App\Models\F24::find($this->filteredByTax);
        }

        return view('livewire.f24-list', [
            'f24s' => $f24s,
            'unpaidCount' => $unpaidCount,
            'paidCount' => $paidCount,
            'unpaidTotal' => $unpaidTotal,
            'filteredF24' => $filteredF24,
        ]);
    }
}