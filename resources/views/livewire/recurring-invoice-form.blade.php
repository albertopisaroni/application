<div class="mx-auto py-8">
<div class="flex gap-8">
    {{-- FORM FATTURA RICORRENTE --}}
    <div class="w-1/2">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Crea Fattura Ricorrente</h2>
            @if($fromSubscription)
                <span class="text-sm bg-green-100 text-green-800 px-3 py-1 rounded-full">
                    📋 Da Abbonamento Stripe
                </span>
            @endif
        </div>

        {{-- Messaggio precompilazione --}}
        @if($fromSubscription)
            <div class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium text-green-800">Dati precompilati da abbonamento Stripe</span>
                </div>
                <p class="text-sm text-green-700 mt-1">
                    Puoi modificare tutti i valori prima di salvare.
                </p>
            </div>
        @endif

        {{-- Sezione Numerazione Fattura --}}
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block font-semibold mb-1">Numerazione Fattura</label>
                <select wire:model.live="selectedNumberingId" class="w-full border rounded px-3 py-2">
                    <option value="">Seleziona numerazione</option>
                    @foreach ($numberings as $numbering)
                        <option value="{{ $numbering->id }}">
                            {{ $numbering->name }} {{ $numbering->type === 'custom' && $numbering->prefix ? '(' . $numbering->prefix . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex space-x-2">
                <div class="w-1/4">
                    <label class="block font-semibold mb-1">Prefisso</label>
                    <input type="text" wire:model="invoicePrefix" class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
                </div>
                <div class="w-3/4">
                    <label class="block font-semibold mb-1">Template</label>
                    <input type="text" wire:model="templateName" class="w-full border rounded px-3 py-2" 
                           placeholder="Es: Abbonamento mensile servizio X">
                    <p class="text-sm text-gray-500 mt-1">Nome per identificare questo template</p>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <label class="block font-semibold mb-1">Tipo Documento</label>
            <select wire:model.live="documentType" class="w-full border rounded px-3 py-2">
                @foreach ($documentTypes as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="mt-4" @if(!in_array($documentType,['TD24','TD25'])) style="display:none" @endif>
            <label class="block font-semibold mb-1">Numero DDT</label>
            <input type="text" wire:model="ddt_number" class="w-full border rounded px-3 py-2" />
            <label class="block font-semibold mt-2 mb-1">Data DDT</label>
            <input type="date" wire:model="ddt_date" class="w-full border rounded px-3 py-2" />
        </div>

        {{-- Dati principali --}}
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="relative">
                <input type="text" wire:model.live.debounce.100ms="client_search" placeholder="Cerca cliente..."
                    class="w-full border rounded px-3 py-2" autocomplete="off">

                @if (!empty($clientSuggestions))
                    <ul class="absolute bg-white border rounded shadow w-full mt-1 z-10">
                        @foreach ($clientSuggestions as $client)
                            <li wire:click="selectClient({{ $client['id'] }})"
                                class="px-3 py-2 hover:bg-gray-100 cursor-pointer">
                                {{ $client['name'] }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div>
                <label class="block mb-1 font-semibold">Data inizio ricorrenza</label>
                <input type="date" wire:model="startDate" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        {{-- Righe fattura --}}
        <h3 class="text-lg font-semibold mb-2">Articoli</h3>

        <div class="space-y-4 mb-6">
            @foreach ($items as $index => $item)
                <div class="border p-4 rounded shadow-sm bg-white">
                    <div class="grid grid-cols-6 gap-2 items-end">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium mb-1">Nome</label>
                            <input type="text" wire:model.live="items.{{ $index }}.name" class="w-full border rounded px-2 py-1" maxlength="100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Qtà</label>
                            <input type="number" step="0.01" wire:model.live="items.{{ $index }}.quantity" class="w-full border rounded px-2 py-1">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Prezzo</label>
                            <input type="number" step="0.01" wire:model.live="items.{{ $index }}.unit_price" class="w-full border rounded px-2 py-1">
                        </div>
                        @if($company->regime_fiscale !== 'RF19')
                            <div>
                                <label class="block text-sm font-medium mb-1">IVA %</label>
                                <input type="number" step="0.01" wire:model.live="items.{{ $index }}.vat_rate" class="w-full border rounded px-2 py-1">
                            </div>
                        @endif
                        <div>
                            <button type="button" wire:click="removeItem({{ $index }})" class="text-red-500 hover:underline text-sm">Rimuovi</button>
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="block text-sm font-medium mb-1">Descrizione</label>
                        <textarea maxlength="200" wire:model.live="items.{{ $index }}.description" rows="2" class="w-full border rounded px-2 py-1 text-sm"></textarea>
                    </div>
                </div>
            @endforeach

            <button type="button" wire:click="addItem" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                + Aggiungi riga
            </button>
        </div>

        {{-- Sconto globale --}}
        <div class="bg-gray-100 p-4 rounded shadow mb-6">
            <div>
                <label class="block text-sm font-medium mb-1">Sconto globale</label>
                <input type="number" step="0.01" wire:model.live="globalDiscount" class="w-full border rounded px-2 py-1">
            </div>
        </div>

        <div class="mb-6">
            <label class="block mb-1 font-semibold">Metodo di pagamento</label>
            <select wire:model.live="selectedPaymentMethodId" class="w-full border rounded px-3 py-2">
                <option value="">Seleziona metodo</option>
                @foreach($paymentMethods as $method)
                    <option value="{{ $method->id }}">{{ $method->name }} ({{ $method->iban }})</option>
                @endforeach
            </select>
        </div>

        <h3 class="text-lg font-semibold mt-6 mb-2">Rate / scadenze</h3>

        {{-- Checkbox di abilitazione "rate" --}}
        <div class="mb-6">
            <label class="inline-flex items-center space-x-2">
                <input type="checkbox" wire:model.live="splitPayments" class="form-checkbox" />
                <span>Vuoi suddividere l'importo in più pagamenti?</span>
            </label>
        </div>

        {{-- Scadenza singola --}}
        @if (! $splitPayments)
            <div class="mb-6">
            <label class="block mb-1 font-semibold">Data di scadenza</label>
            <div class="inline-flex space-x-2">
                <button 
                type="button" 
                wire:click="setDue('on_receipt')" 
                class="px-3 py-1 border rounded {{ $dueOption==='on_receipt' ? 'bg-orange-200' : '' }}">
                Alla ricezione
                </button>
                <button 
                type="button" 
                wire:click="setDue('15')" 
                class="px-3 py-1 border rounded {{ $dueOption==='15' ? 'bg-orange-200' : '' }}">
                Tra 15 giorni
                </button>
                <button 
                type="button" 
                wire:click="setDue('30')" 
                class="px-3 py-1 border rounded {{ $dueOption==='30' ? 'bg-orange-200' : '' }}">
                Tra 30 giorni
                </button>
                <button 
                type="button" 
                wire:click="setDue('custom')" 
                class="px-3 py-1 border rounded {{ $dueOption==='custom' ? 'bg-orange-200' : '' }}">
                Scegli data
                </button>
            </div>

            @if($customDue)
                <input 
                type="date" 
                wire:model.live="dueDate" 
                class="mt-2 border rounded px-2 py-1" />
            @else
                {{-- mostra la data calcolata (read-only o anche come testo) --}}
                <div class="mt-2 text-sm text-gray-700">Scadenza: {{ $dueDate }}</div>
            @endif
            </div>
        @else
        @foreach($payments as $i => $p)
          <div wire:key="payment-{{ $i }}" class="grid grid-cols-3 gap-2 mb-2 items-center">

            {{-- 1) importo + tipo --}}
            <div class="flex border rounded overflow-hidden">
              <input 
                type="number" step="0.01"
                wire:model.lazy="payments.{{ $i }}.value"
                class="w-2/3 px-2 py-1"
                placeholder="Importo o %"
              />
              <select
                wire:model.lazy="payments.{{ $i }}.type"
                class="w-1/3 border-l px-2 py-1 bg-white"
              >
                <option value="amount">€</option>
                <option value="percent">%</option>
              </select>
            </div>

            {{-- 2) termini --}}
            <select wire:model.lazy="payments.{{ $i }}.term" class="border rounded px-2 py-1">
              @foreach($termsOptions as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
              @endforeach
            </select>

            {{-- 3) data --}}
            <input 
              type="date"
              wire:model.lazy="payments.{{ $i }}.date"
              class="border rounded px-2 py-1"
            />

            {{-- elimina --}}
            <button 
              type="button"
              wire:click="removePayment({{ $i }})"
              class="text-red-600 text-sm"
            >×</button>
          </div>
        @endforeach

        <button type="button"
                wire:click="addPayment"
                class="bg-blue-500 text-white px-3 py-1 rounded text-sm">
          + Aggiungi rata
        </button>
        @endif

        {{-- Tipo di Ricorrenza --}}
        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Tipo di Ricorrenza</h3>
            <div class="space-y-3">
                <label class="flex items-center">
                    <input type="radio" wire:model.live="recurrenceMode" value="manual" class="rounded border-gray-300 text-blue-600 shadow-sm">
                    <span class="ml-2 text-sm text-gray-700">
                        <strong>Ricorrenza Manuale</strong> - Imposta manualmente frequenza e date
                    </span>
                </label>
                <label class="flex items-center">
                    <input type="radio" wire:model.live="recurrenceMode" value="stripe" class="rounded border-gray-300 text-blue-600 shadow-sm">
                    <span class="ml-2 text-sm text-gray-700">
                        <strong>Ricorrenza Automatica Stripe</strong> - Sincronizzata con abbonamento Stripe
                    </span>
                </label>
            </div>
        </div>

        {{-- Integrazione Stripe (solo se ricorrenza automatica) --}}
        @if($recurrenceMode === 'stripe')
        <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"/>
                </svg>
                Integrazione Stripe
            </h3>
            
            <div class="space-y-4">
                <!-- Ricerca per nome cliente -->
                <div>
                    <label class="block font-semibold mb-1">Cerca Cliente per Abbonamenti Stripe</label>
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            id="client-search" 
                            class="flex-1 border rounded px-3 py-2" 
                            placeholder="Digita il nome del cliente per cercare i suoi abbonamenti..."
                        >
                        <button 
                            type="button" 
                            id="show-all-subscriptions"
                            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm"
                        >
                            Mostra Tutti
                        </button>
                    </div>
                    <p class="text-xs text-blue-600 mt-1" id="subscription-count">
                        <!-- Il conteggio delle subscription apparirà qui -->
                    </p>
                </div>

                <!-- Tabella abbonamenti -->
                <div id="subscriptions-table-container" class="hidden">
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <h4 class="text-sm font-medium text-gray-700">Abbonamenti Stripe Disponibili</h4>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Prodotto</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Importo</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Scadenza</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Azione</th>
                                    </tr>
                                </thead>
                                <tbody id="subscriptions-table-body">
                                    <!-- Le righe degli abbonamenti appariranno qui -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Abbonamento selezionato -->
                <div id="selected-subscription" class="hidden p-3 bg-green-50 border border-green-200 rounded">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-medium text-green-700">Abbonamento Selezionato:</span>
                        <span class="text-sm text-green-600" id="selected-subscription-text"></span>
                    </div>
                    <button type="button" onclick="clearSelectedSubscription()" class="mt-2 text-xs text-red-600 hover:text-red-800">
                        ✗ Rimuovi selezione
                    </button>
                </div>

                <!-- Checkbox trigger -->
                <div class="flex items-center">
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="triggerOnPayment" 
                               class="rounded border-gray-300 text-blue-600 shadow-sm">
                        <span class="ml-2 text-sm text-gray-700">
                            Genera fattura al pagamento Stripe
                        </span>
                    </label>
                </div>
            </div>
        </div>
        @endif

        {{-- Impostazioni Ricorrenza (solo se ricorrenza manuale) --}}
        @if($recurrenceMode === 'manual')
        <div class="mb-6 p-4 bg-purple-50 rounded-lg border border-purple-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                </svg>
                Impostazioni Ricorrenza
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block font-semibold mb-1">Tipo Ricorrenza</label>
                    <select wire:model="recurrenceType" class="w-full border rounded px-3 py-2">
                        @foreach($recurrenceTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Ogni</label>
                    <input type="number" wire:model="recurrenceInterval" min="1" 
                           class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold mb-1">Data Inizio</label>
                    <input type="date" wire:model="startDate" 
                           class="w-full border rounded px-3 py-2">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold mb-1">Data Fine (opzionale)</label>
                    <input type="date" wire:model="endDate" 
                           class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold mb-1">Numero Max Fatture (opzionale)</label>
                    <input type="number" wire:model="maxInvoices" min="1" 
                           class="w-full border rounded px-3 py-2">
                </div>
            </div>
        </div>
        @endif

        <div class="mb-4">
            <label class="block font-semibold mb-1">Intestazione (opzionale)</label>
            <textarea wire:model.live="headerNotes" class="w-full border rounded px-3 py-2"></textarea>
        </div>

        <div class="mb-4">
            <label class="block font-semibold mb-1">Note aggiuntive (opzionale)</label>
            <textarea wire:model.live="footerNotes" class="w-full border rounded px-3 py-2"></textarea>
        </div>

        <div class="mt-4">
            <label class="block font-semibold mb-1">Testo introduttivo email (verrà inserito all'inizio della mail di invio fattura)</label>
            <textarea wire:model.defer="contactInfo" rows="3" class="w-full border rounded px-3 py-2" placeholder="Inserisci qui il testo che vuoi venga mostrato all'inizio della mail"></textarea>
            <div class="mt-2 flex items-center">
                <input type="checkbox" wire:model="saveNotesForFuture" id="saveNotesForFuture" class="mr-2">
                <label for="saveNotesForFuture" class="text-sm">Salva come default per questa numerazione</label>
            </div>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-4 rounded mb-4 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Salva --}}
        <div class="text-right mt-6">
            <button type="button" wire:click="save" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                Salva Fattura Ricorrente
            </button>
        </div>

    </div>

    {{-- PREVIEW TEMPLATE (identica alle fatture normali) --}}
    <div class="w-1/2 border p-4 rounded bg-white shadow max-h-[90vh] overflow-y-scroll text-sm">
        {!! $this->previewHtml !!}
    </div>
</div>
</div>

<script>
// === STRIPE SUBSCRIPTIONS FUNCTIONALITY ===

// Load subscriptions data from server
const subscriptionsData = @json($subscriptions);
console.log('Subscriptions loaded:', subscriptionsData.length);

// Show subscription count immediately
document.getElementById('subscription-count').textContent = `${subscriptionsData.length} abbonamenti Stripe disponibili nel sistema`;

// Function to render subscriptions table (come prima)
function renderSubscriptionsTable(subscriptions) {
    const tableContainer = document.getElementById('subscriptions-table-container');
    const tableBody = document.getElementById('subscriptions-table-body');
    
    if (subscriptions.length === 0) {
        tableContainer.classList.add('hidden');
        document.getElementById('subscription-count').textContent = 'Nessun abbonamento trovato';
        return;
    }
    
    tableBody.innerHTML = '';
    subscriptions.forEach(sub => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        row.innerHTML = `
            <td class="px-3 py-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="text-xs font-medium text-blue-600">${sub.client_name.charAt(0).toUpperCase()}</span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 text-sm">${sub.client_name}</div>
                        <div class="text-xs text-gray-500">${sub.id}</div>
                    </div>
                </div>
            </td>
            <td class="px-3 py-3 border-b border-gray-100">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    ${sub.name}
                </span>
            </td>
            <td class="px-3 py-3 border-b border-gray-100 font-medium">
                €${sub.amount}
            </td>
            <td class="px-3 py-3 border-b border-gray-100 text-gray-600 text-sm">
                ${sub.period_end}
            </td>
            <td class="px-3 py-3 border-b border-gray-100">
                <button type="button" onclick="selectSubscription('${sub.id}', '${sub.name}', '${sub.client_name}', ${sub.client_id})" 
                        class="px-3 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600 transition-colors">
                    Seleziona
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
    
    tableContainer.classList.remove('hidden');
    document.getElementById('subscription-count').textContent = `${subscriptions.length} abbonamenti trovati`;
}

// Function to select a subscription
function selectSubscription(subscriptionId, subscriptionName, clientName, clientId) {
    // Update Livewire properties
    @this.set('stripeSubscriptionId', subscriptionId);
    @this.set('selectedClientId', clientId);
    @this.set('triggerOnPayment', true);
    
    // Update UI
    document.getElementById('selected-subscription').classList.remove('hidden');
    document.getElementById('selected-subscription-text').textContent = `${subscriptionName} - ${clientName} (${subscriptionId})`;
    document.getElementById('subscriptions-table-container').classList.add('hidden');
}

// Function to clear selected subscription
function clearSelectedSubscription() {
    @this.set('stripeSubscriptionId', '');
    @this.set('triggerOnPayment', false);
    document.getElementById('selected-subscription').classList.add('hidden');
}

// Handle client search input (search by name)
document.getElementById('client-search').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();
    
    if (searchTerm.length === 0) {
        document.getElementById('subscriptions-table-container').classList.add('hidden');
        document.getElementById('subscription-count').textContent = `${subscriptionsData.length} abbonamenti Stripe disponibili nel sistema`;
        return;
    }
    
    // Filter subscriptions by client name
    const filteredSubscriptions = subscriptionsData.filter(sub => 
        sub.client_name.toLowerCase().includes(searchTerm)
    );
    
    renderSubscriptionsTable(filteredSubscriptions);
});

// Handle "Show All" button
document.getElementById('show-all-subscriptions').addEventListener('click', function() {
    renderSubscriptionsTable(subscriptionsData);
    document.getElementById('client-search').value = '';
});

// Show prefilled subscription if available
@if($fromSubscription)
    document.addEventListener('DOMContentLoaded', function() {
        const prefillSubscription = subscriptionsData.find(sub => sub.id === '{{ $stripeSubscriptionId }}');
        if (prefillSubscription) {
            document.getElementById('selected-subscription').classList.remove('hidden');
            document.getElementById('selected-subscription-text').textContent = 
                `${prefillSubscription.name} - ${prefillSubscription.client_name} ({{ $stripeSubscriptionId }})`;
        }
    });
@endif
</script>