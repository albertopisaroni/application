
@foreach($items as $i => $item)
  <tr>
    <td class="border-b py-3 pl-3">{{ $i + 1 }}</td>
    <td class="border-b py-3 pl-2">
      {{ $item['name'] }}
      @if(! empty($item['description']))
        – {{ $item['description'] }}
      @endif
    </td>
    <td class="border-b py-3 pl-2 text-right">
      {{ number_format((float) ($item['quantity'] ?? $item['quantita']), 2, ',', '.') }}
    </td>
    <td class="border-b py-3 pl-2 text-right">
      €{{ number_format((float) ($item['unit_price'] ?? $item['prezzo']), 2, ',', '.') }}
    </td>
    <td class="border-b py-3 pl-2 text-right">
      {{ number_format((float) ($item['vat_rate'] ?? $item['iva']), 0, ',', '.') }}%
    </td>
    <td class="border-b py-3 pl-2 pr-4 text-right">
      €{{ number_format(
        (float) ($item['quantity'] ?? $item['quantita']) 
        * (float) ($item['unit_price'] ?? $item['prezzo']), 
        2, ',', '.')
      }}
    </td>
  </tr>
@endforeach