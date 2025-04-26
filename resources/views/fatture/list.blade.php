<x-app-layout>
    <div class="container mx-auto py-8">
        <h1 class="text-2xl font-bold mb-4">Elenco Fatture</h1>

        <a wire:navigate href="{{ route('fatture.nuova') }}" class="bg-blue-600 text-white px-4 py-2 rounded">Crea Nuova Fattura</a>

        <table class="min-w-full mt-4 border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border p-2">Numero Fattura</th>
                    <th class="border p-2">Data</th>
                    <th class="border p-2">Cliente</th>
                    <th class="border p-2">Totale</th>
                    <th class="border p-2">Azioni</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoices as $invoice)
                    <tr>
                        <td class="border p-2">{{ $invoice->invoice_number }}</td>
                        <td class="border p-2">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                        <td class="border p-2">{{ $invoice->client_name }}</td>
                        <td class="border p-2">{{ number_format($invoice->total, 2, ',', '.') }} &euro;</td>
                        <td class="border p-2">
                            <!-- Qui puoi aggiungere pulsanti per visualizzare, modificare o inviare la fattura -->
                            <a href="#" class="text-blue-600">Visualizza</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $invoices->links() }}
        </div>
    </div>
</x-app-layout>
