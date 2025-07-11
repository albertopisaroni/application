<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Calcolatore Tasse</h1>
        <p class="text-gray-600">Calcolo tasse basato su tutte le fatture del {{ $selectedYear }}</p>
    </div>

    <!-- Selezione Anno -->
    <div class="mb-6">
        <label for="year" class="block text-sm font-medium text-gray-700 mb-2">Anno di riferimento</label>
        <select wire:model.live="selectedYear" id="year" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            @for($year = $currentYear; $year >= $currentYear - 5; $year--)
                <option value="{{ $year }}">{{ $year }}</option>
            @endfor
        </select>
    </div>

    <!-- Riepilogo Fatture -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Riepilogo Fatture {{ $selectedYear }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ count($yearlyInvoices) }}</div>
                <div class="text-sm text-gray-600">Numero Fatture</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">€{{ number_format($taxCalculations['total_revenue'] ?? 0, 2, ',', '.') }}</div>
                <div class="text-sm text-gray-600">Fatturato Totale</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">€{{ number_format($yearlyInvoices->sum('vat') ?? 0, 2, ',', '.') }}</div>
                <div class="text-sm text-gray-600">IVA Totale</div>
            </div>
        </div>
    </div>

    <!-- Calcolo Tasse -->
    @if(!empty($taxCalculations))
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Calcolo Tasse {{ $selectedYear }}</h2>
        
        <!-- Parametri Azienda -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-medium text-gray-900 mb-2">Parametri Azienda</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Regime:</span>
                    <span class="font-medium">{{ $taxCalculations['regime'] }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Coefficiente:</span>
                    <span class="font-medium">{{ $taxCalculations['coefficient'] }}%</span>
                </div>
                <div>
                    <span class="text-gray-600">Aliquota IRPEF:</span>
                    <span class="font-medium">{{ $taxCalculations['aliquota_irpef'] }}%</span>
                </div>
                <div>
                    <span class="text-gray-600">Startup:</span>
                    <span class="font-medium">{{ $taxCalculations['startup'] ? 'Sì' : 'No' }}</span>
                </div>
            </div>
        </div>

        <!-- Calcoli -->
        <div class="space-y-4">
            <div class="flex justify-between items-center py-2 border-b">
                <span class="text-gray-600">Fatturato Totale:</span>
                <span class="font-medium">€{{ number_format($taxCalculations['total_revenue'], 2, ',', '.') }}</span>
            </div>
            
            <div class="flex justify-between items-center py-2 border-b">
                <span class="text-gray-600">Imponibile Forfettario ({{ $taxCalculations['coefficient'] }}%):</span>
                <span class="font-medium">€{{ number_format($taxCalculations['imponibile_forfettario'], 2, ',', '.') }}</span>
            </div>
            
            <div class="flex justify-between items-center py-2 border-b">
                <span class="text-gray-600">Bollo:</span>
                <span class="font-medium">€{{ number_format($taxCalculations['bollo'], 2, ',', '.') }}</span>
            </div>
            
            <div class="flex justify-between items-center py-2 border-b">
                <span class="text-gray-600">Contributi Fissi:</span>
                <span class="font-medium">€{{ number_format($taxCalculations['contributi_fissi'], 2, ',', '.') }}</span>
            </div>
            
            <div class="flex justify-between items-center py-2 border-b">
                <span class="text-gray-600">INPS ({{ $taxCalculations['regime'] == 'Gestione Separata' ? '26.07%' : '24%' }}):</span>
                <span class="font-medium">€{{ number_format($taxCalculations['inps_totale'], 2, ',', '.') }}</span>
            </div>
            
            <div class="flex justify-between items-center py-2 border-b">
                <span class="text-gray-600">IRPEF ({{ $taxCalculations['aliquota_irpef'] }}%):</span>
                <span class="font-medium">€{{ number_format($taxCalculations['irpef'], 2, ',', '.') }}</span>
            </div>
            
            <div class="flex justify-between items-center py-3 border-t-2 border-gray-200">
                <span class="text-lg font-semibold text-red-600">TOTALE TASSE:</span>
                <span class="text-lg font-bold text-red-600">€{{ number_format($taxCalculations['total_taxes'], 2, ',', '.') }}</span>
            </div>
            
            <div class="flex justify-between items-center py-3 border-t-2 border-green-200">
                <span class="text-lg font-semibold text-green-600">NETTO POST TASSE:</span>
                <span class="text-lg font-bold text-green-600">€{{ number_format($taxCalculations['netto_post_tasse'], 2, ',', '.') }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Scadenze -->
    @if(!empty($this->scadenze))
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Scadenze Fiscali {{ $selectedYear }}</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Importo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scadenza</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($this->scadenze as $scadenza)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $scadenza['tipo'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            €{{ number_format($scadenza['importo'], 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $scadenza['scadenza'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $scadenza['note'] }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
