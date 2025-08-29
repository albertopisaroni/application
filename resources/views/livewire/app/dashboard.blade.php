<div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4">
    {{-- Tile Fatture --}}
    <div class="bg-white rounded-lg shadow p-4">
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-lg font-medium">Andamento Fatture ({{ now()->year }})</h2>
        <div class="text-right">
          <div class="text-sm text-gray-600">Totale Anno</div>
          <div class="text-lg font-bold text-green-600">€{{ number_format($invoiceYearTotal, 2, ',', '.') }}</div>
        </div>
      </div>
      <canvas
        id="invoiceChart"
        height="150"
        data-months='@json($months)'
        data-inv-data='@json($invoiceTotals)'
        data-inv-subtotal='@json($invoiceSubtotals)'
      ></canvas>
    </div>
  
    {{-- Tile Abbonamenti --}}
    <div class="bg-white rounded-lg shadow p-4">
              <div class="flex justify-between items-center mb-2">
          <h2 class="text-lg font-medium">Abbonamenti ({{ now()->year }})</h2>
          <div class="text-right">
            <div class="flex gap-4">
              <div class="text-center">
                <div class="text-sm text-gray-600">Totale Anno</div>
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
      ></canvas>
    </div>
  
    {{-- Tile Netto (Fatture - IVA - Spese) --}}
    <div class="bg-white rounded-lg shadow p-4">
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-lg font-medium">Netto ({{ now()->year }})</h2>
        <div class="text-right">
          <div class="text-sm text-gray-600">Totale Anno</div>
          <div class="text-lg font-bold text-green-600">€{{ number_format($invoiceNetYearTotal, 2, ',', '.') }}</div>
        </div>
      </div>
      <canvas
        id="netChart"
        height="150"
        data-months='@json($months)'
        data-net-data='@json($invoiceNetTotals)'
        data-net-forecast='@json($invoiceNetForecast)'
      ></canvas>
    </div>
  </div>