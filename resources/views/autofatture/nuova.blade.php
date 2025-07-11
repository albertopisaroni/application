<x-app-layout>
    <div class="px-2">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-normal">Nuova autofattura</h1>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <form method="POST" action="{{ route('autofatture.store') }}">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Informazioni base -->
                    <div class="space-y-4">
                        <div>
                            <label for="numbering_id" class="block text-sm font-medium text-gray-700">Numerazione</label>
                            <select name="numbering_id" id="numbering_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                                <option value="">Seleziona numerazione</option>
                                @foreach($numberings as $numbering)
                                    <option value="{{ $numbering->id }}">{{ $numbering->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="invoice_date" class="block text-sm font-medium text-gray-700">Data autofattura</label>
                            <input type="date" name="invoice_date" id="invoice_date" value="{{ date('Y-m-d') }}" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                        </div>

                        <div>
                            <label for="document_type" class="block text-sm font-medium text-gray-700">Tipo documento</label>
                            <select name="document_type" id="document_type" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                                <option value="TD16">TD16 - Autofattura per acquisti di servizi da soggetti non residenti</option>
                                <option value="TD17">TD17 - Autofattura per acquisti di servizi da soggetti non residenti in ambito UE</option>
                                <option value="TD18">TD18 - Autofattura per acquisti di beni da soggetti non residenti</option>
                                <option value="TD19">TD19 - Autofattura per acquisti di beni da soggetti non residenti in ambito UE</option>
                                <option value="TD20">TD20 - Autofattura per acquisti di beni da soggetti non residenti in ambito UE</option>
                                <option value="TD21">TD21 - Autofattura per acquisti di beni da soggetti non residenti in ambito UE</option>
                                <option value="TD27">TD27 - Autofattura per acquisti di beni da soggetti non residenti in ambito UE</option>
                            </select>
                        </div>
                    </div>

                    <!-- Informazioni cliente -->
                    <div class="space-y-4">
                        <div>
                            <label for="client_name" class="block text-sm font-medium text-gray-700">Nome cliente</label>
                            <input type="text" name="client_name" id="client_name" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                        </div>

                        <div>
                            <label for="client_address" class="block text-sm font-medium text-gray-700">Indirizzo cliente</label>
                            <input type="text" name="client_address" id="client_address" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label for="client_email" class="block text-sm font-medium text-gray-700">Email cliente</label>
                            <input type="email" name="client_email" id="client_email" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label for="client_phone" class="block text-sm font-medium text-gray-700">Telefono cliente</label>
                            <input type="text" name="client_phone" id="client_phone" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>

                <!-- Articoli -->
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Articoli</h3>
                    <div id="items-container">
                        <div class="item-row grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nome articolo</label>
                                <input type="text" name="items[0][name]" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Quantità</label>
                                <input type="number" name="items[0][quantity]" value="1" min="0" step="0.01" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Prezzo unitario</label>
                                <input type="number" name="items[0][price]" min="0" step="0.01" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">IVA (%)</label>
                                <input type="number" name="items[0][vat]" value="0" min="0" max="100" step="0.1" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addItem()" class="text-sm text-blue-600 hover:text-blue-800">+ Aggiungi articolo</button>
                </div>

                <!-- Totali -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="subtotal" class="block text-sm font-medium text-gray-700">Subtotale</label>
                        <input type="number" name="subtotal" id="subtotal" min="0" step="0.01" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label for="vat" class="block text-sm font-medium text-gray-700">IVA</label>
                        <input type="number" name="vat" id="vat" min="0" step="0.01" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label for="total" class="block text-sm font-medium text-gray-700">Totale</label>
                        <input type="number" name="total" id="total" min="0" step="0.01" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                    </div>
                </div>

                <!-- Pulsanti -->
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('autofatture.lista') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Annulla
                    </a>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-black rounded-md hover:bg-gray-800">
                        Crea autofattura
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let itemCount = 1;

        function addItem() {
            const container = document.getElementById('items-container');
            const newRow = document.createElement('div');
            newRow.className = 'item-row grid grid-cols-1 md:grid-cols-4 gap-4 mb-4';
            newRow.innerHTML = `
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome articolo</label>
                    <input type="text" name="items[${itemCount}][name]" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Quantità</label>
                    <input type="number" name="items[${itemCount}][quantity]" value="1" min="0" step="0.01" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Prezzo unitario</label>
                    <input type="number" name="items[${itemCount}][price]" min="0" step="0.01" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">IVA (%)</label>
                    <input type="number" name="items[${itemCount}][vat]" value="0" min="0" max="100" step="0.1" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                </div>
            `;
            container.appendChild(newRow);
            itemCount++;
        }
    </script>
</x-app-layout> 