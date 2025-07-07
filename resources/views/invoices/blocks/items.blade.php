
@foreach($items as $i => $item)
  <tr>
    <td class="border-b py-3 pl-3">{{ $i + 1 }}</td>
    <td class="border-b py-3 pl-2">
      {{ $item['name'] }}
      @if(! empty($item['description']))
        <div style="font-size: 0.75rem; line-height: 1rem; color: rgb(107 114 128); padding-right:.5rem; word-break: break-word; overflow-wrap: break-word;" class="mt-1">
          {{ $item['description'] }}
        </div>
      @endif
    </td>
    <td class="border-b py-3 pl-2 text-right">
      {{ number_format((float) ($item['quantity'] ?? $item['quantita']), 2, ',', '.') }}
    </td>
    <td class="border-b py-3 pl-2 text-right">
      €{{ number_format((float) ($item['unit_price'] ?? $item['prezzo']), 2, ',', '.') }}
    </td>
    @if($company->regime_fiscale !== 'RF19')
      <td class="border-b py-3 pl-2 text-right">
        {{ number_format((float) ($item['vat_rate'] ?? $item['iva']), 0, ',', '.') }}%
      </td>
    @endif
    <td class="border-b py-3 pl-2 pr-4 text-right">
      €{{ number_format(
        (float) ($item['quantity'] ?? $item['quantita']) 
        * (float) ($item['unit_price'] ?? $item['prezzo']), 
        2, ',', '.')
      }}
    </td>
  </tr>
@endforeach