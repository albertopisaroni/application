
<div>
  <div class="flex justify-between items-center mb-6 p-4">
    <div></div> {{-- Spazio vuoto a sinistra --}}
    
    <div class="flex items-center space-x-2">
        {{-- Selettore principale --}}
        <div class="flex bg-gray-100 rounded-lg p-1">
            <button 
                wire:click="setCustomRange"
                class="flex items-center px-3 py-1 text-sm rounded-md transition-colors {{ $dateRange === 'custom' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Custom
            </button>
            
            <button 
                wire:click="setDateRange('1M')" 
                class="px-3 py-1 text-sm rounded-md transition-colors {{ $dateRange === '1M' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
            >
                1M
            </button>
            
            <button 
                wire:click="setDateRange('3M')" 
                class="px-3 py-1 text-sm rounded-md transition-colors {{ $dateRange === '3M' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
            >
                3M
            </button>
            
            <button 
                wire:click="setDateRange('6M')" 
                class="px-3 py-1 text-sm rounded-md transition-colors {{ $dateRange === '6M' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
            >
                6M
            </button>
            
            <button 
                wire:click="setDateRange('12M')" 
                class="px-3 py-1 text-sm rounded-md transition-colors {{ $dateRange === '12M' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
            >
                12M
            </button>
        </div>
        
        {{-- Selettore date personalizzate --}}
        @if($showCustomDatePicker)
        <div class="flex items-center space-x-2 bg-white border border-gray-300 rounded-lg p-2 shadow-lg">
            <div>
                <label class="block text-xs text-gray-500">Data inizio</label>
                <input 
                    type="date" 
                    wire:model.live="customStartDate"
                    class="text-sm border-0 focus:ring-0 p-0"
                    max="{{ $customEndDate }}"
                >
            </div>
            <div class="text-gray-400">→</div>
            <div>
                <label class="block text-xs text-gray-500">Data fine</label>
                <input 
                    type="date" 
                    wire:model.live="customEndDate"
                    class="text-sm border-0 focus:ring-0 p-0"
                    min="{{ $customStartDate }}"
                >
            </div>
            <button 
                wire:click="hideCustomDatePicker"
                class="text-gray-400 hover:text-gray-600"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        @endif
        
        {{-- Indicatore granularità --}}
        @php
            $isWeekly = $dateRange === '1M' || $dateRange === '3M' || 
                       ($dateRange === 'custom' && $customStartDate && $customEndDate && 
                        \Carbon\Carbon::parse($customStartDate)->diffInMonths(\Carbon\Carbon::parse($customEndDate)) <= 3);
        @endphp
        @if($isWeekly)
            <div class="text-xs text-gray-500 bg-blue-50 px-2 py-1 rounded">
                Visualizzazione settimanale
            </div>
        @else
            <div class="text-xs text-gray-500 bg-green-50 px-2 py-1 rounded">
                Visualizzazione mensile
            </div>
        @endif
    </div>
</div>

{{-- Messaggi di errore per date personalizzate --}}
@if($errors->has('customEndDate'))
    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm text-red-600">{{ $errors->first('customEndDate') }}</p>
    </div>
@endif

{{-- Messaggio se non ci sono dati --}}
@if(empty($months))
    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-sm text-blue-600">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Nessun dato disponibile per il periodo selezionato.
        </p>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4">
    {{-- Tile Fatture --}}
    <div class="bg-white rounded-lg shadow p-4">
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-lg font-medium">
            Andamento Fatture 
            @if($dateRange === 'custom' && $customStartDate && $customEndDate)
                ({{ \Carbon\Carbon::parse($customStartDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($customEndDate)->format('d/m/Y') }})
            @elseif($dateRange === '1M')
                (Mese corrente)
            @elseif($dateRange === '3M')
                (Ultimi 3 mesi)
            @elseif($dateRange === '6M')
                (Ultimi 6 mesi)
            @else
                ({{ now()->year }})
            @endif
        </h2>
        <div class="text-right">
          <div class="text-sm text-gray-600">Totale Periodo</div>
          <div class="text-lg font-bold text-green-600">€{{ number_format($invoiceYearTotal, 2, ',', '.') }}</div>
        </div>
      </div>
      <canvas
        id="invoiceChart"
        height="150"
        data-months='@json($months)'
        data-inv-data='@json($invoiceTotals)'
        data-inv-subtotal='@json($invoiceSubtotals)'
        data-granularity='@json($isWeekly ? "weekly" : "monthly")'
      ></canvas>
    </div>
  
    {{-- Tile Abbonamenti --}}
    <div class="bg-white rounded-lg shadow p-4">
              <div class="flex justify-between items-center mb-2">
          <h2 class="text-lg font-medium">
            Abbonamenti 
            @if($dateRange === 'custom' && $customStartDate && $customEndDate)
                ({{ \Carbon\Carbon::parse($customStartDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($customEndDate)->format('d/m/Y') }})
            @elseif($dateRange === '1M')
                (Mese corrente)
            @elseif($dateRange === '3M')
                (Ultimi 3 mesi)
            @elseif($dateRange === '6M')
                (Ultimi 6 mesi)
            @else
                ({{ now()->year }})
            @endif
          </h2>
          <div class="text-right">
            <div class="flex gap-4">
              <div class="text-center">
                <div class="text-sm text-gray-600">Totale Periodo</div>
                <div class="text-lg font-bold text-blue-600">€{{ number_format($subscriptionYearTotal, 2, ',', '.') }}</div>
              </div>
              <div class="text-center">
                <div class="text-sm text-gray-600">Previsione</div>
                <div class="text-lg font-bold text-orange-600">€{{ number_format($subscriptionForecastTotal, 2, ',', '.') }}</div>
              </div>
            </div>
          </div>
        </div>
      <canvas
        id="subscriptionChart"
        height="150"
        data-months='@json($months)'
        data-sub-data='@json($subscriptionTotals)'
        data-sub-forecast='@json($subscriptionForecast)'
        data-granularity='@json($isWeekly ? "weekly" : "monthly")'
      ></canvas>
    </div>
  
    {{-- Tile Netto (Fatture - IVA - Spese) --}}
    <div class="bg-white rounded-lg shadow p-4">
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-lg font-medium">
            Netto 
            @if($dateRange === 'custom' && $customStartDate && $customEndDate)
                ({{ \Carbon\Carbon::parse($customStartDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($customEndDate)->format('d/m/Y') }})
            @elseif($dateRange === '1M')
                (Mese corrente)
            @elseif($dateRange === '3M')
                (Ultimi 3 mesi)
            @elseif($dateRange === '6M')
                (Ultimi 6 mesi)
            @else
                ({{ now()->year }})
            @endif
        </h2>
        <div class="text-right">
          <div class="text-sm text-gray-600">Totale Periodo</div>
          <div class="text-lg font-bold text-green-600">€{{ number_format($invoiceNetYearTotal, 2, ',', '.') }}</div>
        </div>
      </div>
      <canvas
        id="netChart"
        height="150"
        data-months='@json($months)'
        data-net-data='@json($invoiceNetTotals)'
        data-net-forecast='@json($invoiceNetForecast)'
        data-granularity='@json($isWeekly ? "weekly" : "monthly")'
      ></canvas>
    </div>
  </div>
</div>