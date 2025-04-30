<div class="px-14 py-3 text-sm">
    @if($pm->sdi_code == 'MP05')
      <p><strong>Metodo di pagamento:</strong> {{ $pm->type }}</p>
      <p><strong>Banca:</strong> {{ $pm->name }}</p>
      <p><strong>IBAN:</strong> {{ $pm->iban }}</p>
    @elseif($pm->sdi_code == 'MP08')
      <p><strong>Metodo di pagamento:</strong> {{ $pm->type }}</p>
    @endif
  </div>