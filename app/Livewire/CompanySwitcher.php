<?php

namespace App\Livewire;

use Livewire\Component;



class CompanySwitcher extends Component
{
    public $companies;
    public $current;

    public function mount()
    {
        $this->companies = auth()->user()->companies;
        $this->current = session('current_company_id') ?? auth()->user()->current_company_id;
    }

    public function switchCompany($companyId)
    {
        if (!auth()->user()->companies->pluck('id')->contains($companyId)) {
            abort(403);
        }

        session(['current_company_id' => $companyId]);
        auth()->user()->update(['current_company_id' => $companyId]);

        $this->current = $companyId;
        $this->dispatch('company-switched');
    }

    public function render()
    {
        return view('livewire.company-switcher');
    }
}