<x-app-layout>
<div class="px-2">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-normal">Nuova Fattura Ricorrente</h1>
        <a href="{{ route('fatture-ricorrenti.lista') }}" wire:navigate class="text-gray-600 hover:text-gray-800">
            ← Torna alla lista
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('fatture-ricorrenti.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>
                    <select name="client_id" id="client_id" class="w-full border border-gray-300 rounded px-3 py-2" required>
                        <option value="">Seleziona cliente</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="numbering_id" class="block text-sm font-medium text-gray-700 mb-2">Numerazione</label>
                    <select name="numbering_id" id="numbering_id" class="w-full border border-gray-300 rounded px-3 py-2" required>
                        <option value="">Seleziona numerazione</option>
                        @foreach($numberings as $numbering)
                            <option value="{{ $numbering->id }}">{{ $numbering->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-6">
                <label for="template_name" class="block text-sm font-medium text-gray-700 mb-2">Nome Template</label>
                <input type="text" name="template_name" id="template_name" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="Es: Abbonamento mensile servizio X">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label for="recurrence_type" class="block text-sm font-medium text-gray-700 mb-2">Tipo Ricorrenza</label>
                    <select name="recurrence_type" id="recurrence_type" class="w-full border border-gray-300 rounded px-3 py-2" required>
                        <option value="days">Giorni</option>
                        <option value="weeks">Settimane</option>
                        <option value="months" selected>Mesi</option>
                        <option value="years">Anni</option>
                    </select>
                </div>

                <div>
                    <label for="recurrence_interval" class="block text-sm font-medium text-gray-700 mb-2">Ogni</label>
                    <input type="number" name="recurrence_interval" id="recurrence_interval" value="1" min="1" class="w-full border border-gray-300 rounded px-3 py-2" required>
                </div>

                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Data Inizio</label>
                    <input type="date" name="start_date" id="start_date" value="{{ date('Y-m-d') }}" class="w-full border border-gray-300 rounded px-3 py-2" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Data Fine (opzionale)</label>
                    <input type="date" name="end_date" id="end_date" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label for="max_invoices" class="block text-sm font-medium text-gray-700 mb-2">Numero Max Fatture (opzionale)</label>
                    <input type="number" name="max_invoices" id="max_invoices" min="1" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
            </div>

            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Articoli</h3>
                <div id="items-container">
                    <div class="item-row grid grid-cols-12 gap-4 mb-4">
                        <div class="col-span-5">
                            <input type="text" name="items[0][description]" placeholder="Descrizione" class="w-full border border-gray-300 rounded px-3 py-2" required>
                        </div>
                        <div class="col-span-2">
                            <input type="number" name="items[0][quantity]" placeholder="Qtà" value="1" min="1" class="w-full border border-gray-300 rounded px-3 py-2" required>
                        </div>
                        <div class="col-span-2">
                            <input type="number" name="items[0][unit_price]" placeholder="Prezzo" step="0.01" min="0" class="w-full border border-gray-300 rounded px-3 py-2" required>
                        </div>
                        <div class="col-span-2">
                            <input type="number" name="items[0][vat_rate]" placeholder="IVA %" value="22" step="0.01" min="0" class="w-full border border-gray-300 rounded px-3 py-2" required>
                        </div>
                        <div class="col-span-1">
                            <button type="button" onclick="removeItem(this)" class="w-full bg-red-500 text-white px-3 py-2 rounded">×</button>
                        </div>
                        <input type="hidden" name="items[0][total]" value="0">
                    </div>
                </div>
                <button type="button" onclick="addItem()" class="bg-blue-500 text-white px-4 py-2 rounded">Aggiungi Articolo</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label for="subtotal" class="block text-sm font-medium text-gray-700 mb-2">Subtotale</label>
                    <input type="number" name="subtotal" id="subtotal" step="0.01" min="0" class="w-full border border-gray-300 rounded px-3 py-2" required readonly>
                </div>

                <div>
                    <label for="vat" class="block text-sm font-medium text-gray-700 mb-2">IVA</label>
                    <input type="number" name="vat" id="vat" step="0.01" min="0" class="w-full border border-gray-300 rounded px-3 py-2" required readonly>
                </div>

                <div>
                    <label for="total" class="block text-sm font-medium text-gray-700 mb-2">Totale</label>
                    <input type="number" name="total" id="total" step="0.01" min="0" class="w-full border border-gray-300 rounded px-3 py-2" required readonly>
                </div>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('fatture-ricorrenti.lista') }}" wire:navigate class="bg-gray-500 text-white px-6 py-2 rounded">Annulla</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">Crea Fattura Ricorrente</button>
            </div>
        </form>
    </div>
</div>

<script>
let itemIndex = 1;

function addItem() {
    const container = document.getElementById('items-container');
    const newRow = document.createElement('div');
    newRow.className = 'item-row grid grid-cols-12 gap-4 mb-4';
    newRow.innerHTML = `
        <div class="col-span-5">
            <input type="text" name="items[${itemIndex}][description]" placeholder="Descrizione" class="w-full border border-gray-300 rounded px-3 py-2" required>
        </div>
        <div class="col-span-2">
            <input type="number" name="items[${itemIndex}][quantity]" placeholder="Qtà" value="1" min="1" class="w-full border border-gray-300 rounded px-3 py-2" required>
        </div>
        <div class="col-span-2">
            <input type="number" name="items[${itemIndex}][unit_price]" placeholder="Prezzo" step="0.01" min="0" class="w-full border border-gray-300 rounded px-3 py-2" required>
        </div>
        <div class="col-span-2">
            <input type="number" name="items[${itemIndex}][vat_rate]" placeholder="IVA %" value="22" step="0.01" min="0" class="w-full border border-gray-300 rounded px-3 py-2" required>
        </div>
        <div class="col-span-1">
            <button type="button" onclick="removeItem(this)" class="w-full bg-red-500 text-white px-3 py-2 rounded">×</button>
        </div>
        <input type="hidden" name="items[${itemIndex}][total]" value="0">
    `;
    container.appendChild(newRow);
    itemIndex++;
    calculateTotals();
}

function removeItem(button) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length > 1) {
        button.closest('.item-row').remove();
        calculateTotals();
    }
}

function calculateTotals() {
    let subtotal = 0;
    let totalVat = 0;
    
    document.querySelectorAll('.item-row').forEach(row => {
        const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
        const unitPrice = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
        const vatRate = parseFloat(row.querySelector('input[name*="[vat_rate]"]').value) || 0;
        
        const itemSubtotal = quantity * unitPrice;
        const itemVat = itemSubtotal * (vatRate / 100);
        
        subtotal += itemSubtotal;
        totalVat += itemVat;
        
        // Update item total
        const totalInput = row.querySelector('input[name*="[total]"]');
        if (totalInput) {
            totalInput.value = (itemSubtotal + itemVat).toFixed(2);
        }
    });
    
    document.getElementById('subtotal').value = subtotal.toFixed(2);
    document.getElementById('vat').value = totalVat.toFixed(2);
    document.getElementById('total').value = (subtotal + totalVat).toFixed(2);
}

// Add event listeners to recalculate totals when inputs change
document.addEventListener('input', function(e) {
    if (e.target.matches('input[name*="[quantity]"], input[name*="[unit_price]"], input[name*="[vat_rate]"]')) {
        calculateTotals();
    }
});

// Calculate initial totals
calculateTotals();
</script>
</x-app-layout>
