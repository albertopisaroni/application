<div class="mx-auto py-8">
<div class="flex gap-8">
    {{-- FORM FATTURA --}}
    <div class="w-1/2">
    <h2 class="text-2xl font-bold mb-6">Crea nuova fattura</h2>

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
                <label class="block font-semibold mb-1">Numero</label>
                <input type="text"
                    wire:model="invoiceNumber"
                    maxlength="20"
                    class="w-full border rounded px-3 py-2"
                    placeholder="Fino a 20 caratteri" />
                <p class="text-sm text-gray-500 mt-1">Puoi modificarlo manualmente</p>
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
            <label class="block mb-1 font-semibold">Data emissione</label>
            <input type="date" wire:model="invoiceDate" class="w-full border rounded px-3 py-2">
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

{{-- Checkbox di abilitazione “rate” --}}
<div class="mb-6">
    <label class="inline-flex items-center space-x-2">
        <input type="checkbox" wire:model.live="splitPayments" class="form-checkbox" />
        <span>Vuoi suddividere l’importo in più pagamenti?</span>
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


    <div class="mb-4">
        <label class="block font-semibold mb-1">Intestazione (opzionale)</label>
        <textarea wire:model.live="headerNotes" class="w-full border rounded px-3 py-2"></textarea>
    </div>


    <div class="mb-4">
        <label class="block font-semibold mb-1">Note aggiuntive (opzionale)</label>
        <textarea wire:model.live="footerNotes" class="w-full border rounded px-3 py-2"></textarea>
    </div>

    <div class="flex items-center space-x-2">
        <input type="checkbox" wire:model="saveNotesForFuture" id="save_notes" class="rounded border-gray-300">
        <label for="save_notes" class="text-sm">Salva l'intestazione e le note per le prossime fatture</label>
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
            Salva Fattura
        </button>
    </div>

    </div>

    {{-- PREVIEW TEMPLATE --}}
    <div class="w-1/2 border p-4 rounded bg-white shadow max-h-[90vh] overflow-y-scroll text-sm">
        {!! $this->previewHtml !!}
    </div>
</div>
</div>