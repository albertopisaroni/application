<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Client;

class ClientDetail extends Component
{
    public Client $client;
    
    // Merge functionality
    public $showMergeModal = false;
    public $sourceClientId = null;
    public $targetClientId = null;
    public $mergeSearch = '';
    public $suggestedClients = [];
    public $selectedMergeData = [];

    public function mount(Client $client)
    {
        $this->client = $client;
    }

    public function testMethod()
    {
        $this->dispatch('show-success', message: 'Test method called successfully!');
    }

    public function showMergeModal($clientId)
    {
        $this->sourceClientId = $clientId;
        $this->showMergeModal = true;
        $this->loadSuggestedClients();
    }

    public function loadSuggestedClients()
    {
        $sourceClient = Client::find($this->sourceClientId);
        $currentCompanyId = session('current_company_id');

        $query = Client::where('company_id', $currentCompanyId)
            ->where('hidden', false)
            ->where('id', '!=', $this->sourceClientId);

        if ($this->mergeSearch) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->mergeSearch . '%')
                  ->orWhere('piva', 'like', '%' . $this->mergeSearch . '%')
                  ->orWhereHas('primaryContact', fn($sub) =>
                      $sub->where('email', 'like', '%' . $this->mergeSearch . '%')
                  );
            });
        }

        // Add similarity suggestions
        if ($sourceClient) {
            $similarName = Client::where('company_id', $currentCompanyId)
                ->where('hidden', false)
                ->where('id', '!=', $this->sourceClientId)
                ->where('name', 'like', '%' . substr($sourceClient->name, 0, 3) . '%')
                ->limit(3)
                ->get();

            $this->suggestedClients = $query->limit(10)->get()->merge($similarName)->unique('id');
        } else {
            $this->suggestedClients = $query->limit(10)->get();
        }
    }

    public function updatedMergeSearch()
    {
        $this->loadSuggestedClients();
    }

    public function selectTargetClient($clientId)
    {
        $this->targetClientId = $clientId;
        $this->prepareMergeData();
    }

    public function prepareMergeData()
    {
        $sourceClient = Client::find($this->sourceClientId);
        $targetClient = Client::find($this->targetClientId);

        if (!$sourceClient || !$targetClient) {
            return;
        }

        $fields = ['name', 'domain', 'address', 'cap', 'city', 'province', 'country', 'piva', 'sdi', 'pec', 'email', 'phone'];

        foreach ($fields as $field) {
            $sourceValue = $sourceClient->$field;
            $targetValue = $targetClient->$field;

            // Auto-select the better value
            if (empty($sourceValue) && !empty($targetValue)) {
                $this->selectedMergeData[$field] = 'target';
            } elseif (!empty($sourceValue) && empty($targetValue)) {
                $this->selectedMergeData[$field] = 'source';
            } elseif (!empty($sourceValue) && !empty($targetValue)) {
                // If both have values, prefer the more complete one
                $this->selectedMergeData[$field] = strlen($sourceValue) >= strlen($targetValue) ? 'source' : 'target';
            } else {
                $this->selectedMergeData[$field] = 'source'; // Default to source
            }
        }
    }

    public function mergeClients()
    {
        $sourceClient = Client::find($this->sourceClientId);
        $targetClient = Client::find($this->targetClientId);

        if (!$sourceClient || !$targetClient) {
            $this->dispatch('show-error', message: 'Errore: clienti non trovati.');
            return;
        }

        $fields = ['name', 'domain', 'address', 'cap', 'city', 'province', 'country', 'piva', 'sdi', 'pec', 'email', 'phone'];

        // Update target client with selected data
        foreach ($fields as $field) {
            if (isset($this->selectedMergeData[$field])) {
                $value = $this->selectedMergeData[$field] === 'source' ? $sourceClient->$field : $targetClient->$field;
                $targetClient->$field = $value;
            }
        }

        $targetClient->save();

        // Move all related data from source to target
        $this->moveRelatedData($sourceClient->id, $targetClient->id);

        // Hide the source client
        $sourceClient->update(['hidden' => true]);

        $this->showMergeModal = false;
        $this->reset(['sourceClientId', 'targetClientId', 'mergeSearch', 'suggestedClients', 'selectedMergeData']);
        
        // Redirect to the target client detail page
        return redirect()->route('contatti.clienti.show', $targetClient);
    }

    private function moveRelatedData($sourceId, $targetId)
    {
        // Move contacts
        \App\Models\Contact::where('client_id', $sourceId)->update(['client_id' => $targetId]);
        
        // Move invoices
        \App\Models\Invoice::where('client_id', $sourceId)->update(['client_id' => $targetId]);
        
        // Move subscriptions
        \App\Models\Subscription::where('client_id', $sourceId)->update(['client_id' => $targetId]);
        
        // Add any other related models here
    }

    public function closeMergeModal()
    {
        $this->showMergeModal = false;
        $this->reset(['sourceClientId', 'targetClientId', 'mergeSearch', 'suggestedClients', 'selectedMergeData']);
    }

    public function render()
    {
        return view('livewire.client-detail');
    }
} 