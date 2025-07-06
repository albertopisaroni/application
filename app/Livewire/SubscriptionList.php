<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Subscription;
use App\Models\Client;
use Carbon\Carbon;

class SubscriptionList extends Component
{
    use WithPagination;

    public array  $numberings         = [];
    public $numberingFilter           = null;  
    public $perPage                   = 10;
    public $search                    = '';
    public $paymentStatusFilter       = null;


    // metriche del mese
    public int   $renewalsCount   = 0;
    public float $renewalsTotal   = 0.0;

    // nuove metriche
    public int   $activeCount     = 0;
    public float $mrr             = 0.0;

    public function updatingNumberingFilter()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPaymentStatusFilter()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->numberingFilter     = null;
        $this->search              = '';
        $this->paymentStatusFilter = null;
        $this->resetPage();
    }

    public function mount()
    {
        $companyId = session('current_company_id');

        $this->numberings = \App\Models\InvoiceNumbering::whereHas('stripeAccount', function($q) use($companyId) {
            $q->where('company_id', $companyId);
        })->get()->toArray();
    }

    public function render()
    {
        $companyId = session('current_company_id');

        $baseQuery = Subscription::whereHas('client', fn($q) =>
            $q->where('company_id', $companyId)
        );

        // 1) Rinnovi questo mese (sia nuovi start_date che rinnovi end_date)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();

        // 1) Prepara la query base con JOIN su clients
        $subQuery = Subscription::join('clients', 'subscriptions.client_id', '=', 'clients.id')
            ->where('clients.company_id', $companyId);

        // 2) Rinnovi che finiscono questo mese
        $endRenewals = (clone $subQuery)
            ->whereBetween('subscriptions.current_period_end', [$startOfMonth, $endOfMonth]);

        // 3) Rinnovi che partono questo mese
        $startRenewals = (clone $subQuery)
            ->whereBetween('subscriptions.start_date', [$startOfMonth, $endOfMonth]);

        // 4) Conta e somma
        $this->renewalsCount = $endRenewals->count() + $startRenewals->count();
        $this->renewalsTotal = $endRenewals->sum('subscriptions.final_amount')
                            + $startRenewals->sum('subscriptions.final_amount');

        // 2) activeCount + MRR in un’unica query
        $now = Carbon::now();

        $metriche = Subscription::whereHas('client', fn($q) =>
                $q->where('company_id', $companyId)
            )
            ->where('status','active')
            ->where('current_period_end','>=',$now)
            ->selectRaw('
                COUNT(*)           as activeCount,
                COALESCE(SUM(final_amount),0)  as totalFinal
            ')
            ->first();

        $this->activeCount = (int) $metriche->activeCount;
        $this->mrr         = $metriche->totalFinal / 12;

        // 3) Lista paginata (applica anche ordinamenti e filtri se li hai)
        
        $query = $baseQuery
            //  --- filtro numerazione solo se > 0
            ->when($this->numberingFilter > 0, fn($q) =>
                $q->whereHas('client.company.stripeAccounts', fn($qc) =>
                    $qc->where('invoice_numbering_id', $this->numberingFilter)
                )
            )
            //  --- filtro ricerca nome o email
            ->when($this->search, function($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn($q) =>
                    $q->whereHas('client', fn($qc) =>
                        $qc->where('name','like',$term)
                    )->orWhereHas('client.primaryContact', fn($qc) =>
                        $qc->where('email','like',$term)
                    )
                );
            })
            //  --- filtro stato
            ->when($this->paymentStatusFilter, function($q) {
                if ($this->paymentStatusFilter === 'unpaid') {
                    // “Non pagato” = unpaid OR past_due
                    $q->whereIn('status', ['unpaid','past_due']);
                } else {
                    $q->where('status', $this->paymentStatusFilter);
                }
            })
            ->orderBy('start_date','desc')
            ->orderBy('id','desc');

        $subscriptions = $query
            ->with(['price.product','client.primaryContact'])
            ->paginate($this->perPage);

        return view('livewire.subscription-list', [
            'subscriptions'   => $subscriptions,
            'renewalsCount'   => $this->renewalsCount,
            'renewalsTotal'   => $this->renewalsTotal,
            'activeCount'     => $this->activeCount,
            'mrr'             => $this->mrr,
            'numberings'      => $this->numberings,
        ]);
    }
}