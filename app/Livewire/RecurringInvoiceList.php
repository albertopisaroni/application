<?php

namespace App\Livewire;

use App\Models\RecurringInvoice;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class RecurringInvoiceList extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all'; // all, active, inactive
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function toggleActive($recurringInvoiceId)
    {
        $recurringInvoice = RecurringInvoice::findOrFail($recurringInvoiceId);
        
        // Check authorization
        if ($recurringInvoice->company_id !== Auth::user()->current_company_id) {
            return;
        }

        $recurringInvoice->update([
            'is_active' => !$recurringInvoice->is_active
        ]);

        $status = $recurringInvoice->is_active ? 'attivata' : 'disattivata';
        session()->flash('message', "Fattura ricorrente {$status} con successo!");
    }

    public function delete($recurringInvoiceId)
    {
        $recurringInvoice = RecurringInvoice::findOrFail($recurringInvoiceId);
        
        // Check authorization
        if ($recurringInvoice->company_id !== Auth::user()->current_company_id) {
            return;
        }

        $recurringInvoice->delete();
        session()->flash('message', 'Fattura ricorrente eliminata con successo!');
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function render()
    {
        $query = RecurringInvoice::with(['client', 'numbering'])
            ->where('company_id', Auth::user()->current_company_id);

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('template_name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('client', function ($clientQuery) {
                      $clientQuery->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Apply status filter
        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        $recurringInvoices = $query->paginate(15);

        // Get counts for status filters
        $activeCount = RecurringInvoice::where('company_id', Auth::user()->current_company_id)
            ->where('is_active', true)->count();
        
        $inactiveCount = RecurringInvoice::where('company_id', Auth::user()->current_company_id)
            ->where('is_active', false)->count();

        return view('livewire.recurring-invoice-list', compact('recurringInvoices', 'activeCount', 'inactiveCount'));
    }
}
