<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Client;

class ClientList extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $search = '';
    
    // Merge functionality
    public $mergeModalVisible = false;
    public $sourceClientId = null;
    public $targetClientId = null;
    public $mergeSearch = '';
    public $suggestedClients = [];
    public $selectedMergeData = [];

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->resetPage();
    }

    public function hideClient($clientId)
    {
        $client = Client::findOrFail($clientId);
        $client->update(['hidden' => true]);
        
        $this->dispatch('show-success', message: 'Cliente nascosto con successo.');
    }

    public function showMergeModal($clientId)
    {
        $this->sourceClientId = $clientId;
        $this->mergeModalVisible = true;
        
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

        $fields = ['name', 'domain', 'address', 'cap', 'city', 'province', 'country', 'piva', 'sdi', 'pec', 'stripe_customer_id', 'stripe_account_id'];

        foreach ($fields as $field) {
            $sourceValue = $sourceClient->$field;
            $targetValue = $targetClient->$field;

            // Auto-select the better value
            if (empty($sourceValue) && !empty($targetValue)) {
                // Source is empty, target has value - select target
                $this->selectedMergeData[$field] = 'target';
            } elseif (!empty($sourceValue) && empty($targetValue)) {
                // Source has value, target is empty - select source
                $this->selectedMergeData[$field] = 'source';
            } elseif (!empty($sourceValue) && !empty($targetValue)) {
                // Both have values - prefer the more complete one
                if ($field === 'stripe_customer_id') {
                    // For Stripe Customer ID, prefer the one that looks more complete
                    $sourceLength = strlen($sourceValue);
                    $targetLength = strlen($targetValue);
                    $this->selectedMergeData[$field] = $sourceLength >= $targetLength ? 'source' : 'target';
                } elseif ($field === 'stripe_account_id') {
                    // For Stripe Account ID, we'll handle it based on stripe_customer_id selection
                    // So we don't need to set it here
                    continue;
                } else {
                    // For other fields, prefer the more complete one
                    $this->selectedMergeData[$field] = strlen($sourceValue) >= strlen($targetValue) ? 'source' : 'target';
                }
            } else {
                // Both empty - default to source
                $this->selectedMergeData[$field] = 'source';
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

        $fields = ['name', 'domain', 'address', 'cap', 'city', 'province', 'country', 'piva', 'sdi', 'pec', 'stripe_customer_id', 'stripe_account_id'];

        // Update target client with selected data
        foreach ($fields as $field) {
            if (isset($this->selectedMergeData[$field])) {
                $value = $this->selectedMergeData[$field] === 'source' ? $sourceClient->$field : $targetClient->$field;
                $targetClient->$field = $value;
            }
        }

        // Special handling for Stripe fields
        if (isset($this->selectedMergeData['stripe_customer_id'])) {
            if ($this->selectedMergeData['stripe_customer_id'] === 'source') {
                // If we're using source's stripe_customer_id, also copy its stripe_account_id
                $targetClient->stripe_account_id = $sourceClient->stripe_account_id;
            } else {
                // If we're using target's stripe_customer_id, keep its stripe_account_id
                $targetClient->stripe_account_id = $targetClient->stripe_account_id;
            }
        }

        $targetClient->save();

        // Move all related data from source to target
        $this->moveRelatedData($sourceClient->id, $targetClient->id);

        // Hide the source client
        $sourceClient->update(['hidden' => true]);

        $this->mergeModalVisible = false;
        $this->reset(['sourceClientId', 'targetClientId', 'mergeSearch', 'suggestedClients', 'selectedMergeData']);
        
        $this->dispatch('show-success', message: 'Clienti uniti con successo.');
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
        $this->mergeModalVisible = false;
        $this->reset(['sourceClientId', 'targetClientId', 'mergeSearch', 'suggestedClients', 'selectedMergeData']);
    }

    public function mount()
    {
        // Component initialization
    }

    public function render()
    {
        $currentCompanyId = session('current_company_id');

        $query = Client::with(['primaryContact'])
            ->where('company_id', $currentCompanyId)
            ->where('hidden', false)
            ->orderBy('name', 'asc');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('piva', 'like', '%' . $this->search . '%')
                  ->orWhereHas('primaryContact', fn($sub) =>
                      $sub->where('email', 'like', '%' . $this->search . '%')
                  );
            });
        }

        $clients = $query->paginate($this->perPage);

        // Calculate statistics
        $totalClients = Client::where('company_id', $currentCompanyId)
            ->where('hidden', false)
            ->count();

        $clientsWithContacts = Client::where('company_id', $currentCompanyId)
            ->where('hidden', false)
            ->whereHas('contacts')
            ->count();

        $clientsWithoutContacts = $totalClients - $clientsWithContacts;

        return view('livewire.client-list', [
            'clients' => $clients,
            'totalClients' => $totalClients,
            'clientsWithContacts' => $clientsWithContacts,
            'clientsWithoutContacts' => $clientsWithoutContacts,
        ]);
    }
} 