<div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4">
    {{-- Tile Fatture --}}
    <div class="bg-white rounded-lg shadow p-4">
      <h2 class="text-lg font-medium mb-2">Andamento Fatture ({{ now()->year }})</h2>
      <canvas
        id="invoiceChart"
        height="150"
        data-months='@json($months)'
        data-inv-data='@json($invoiceTotals)'
      ></canvas>
    </div>
  
    {{-- Tile Abbonamenti --}}
    <div class="bg-white rounded-lg shadow p-4">
      <h2 class="text-lg font-medium mb-2">Andamento Abbonamenti ({{ now()->year }})</h2>
      <canvas
        id="subscriptionChart"
        height="150"
        data-months='@json($months)'
        data-sub-data='@json($subscriptionTotals)'
        data-sub-forecast='@json($subscriptionForecast)'
      ></canvas>
    </div>
  </div>