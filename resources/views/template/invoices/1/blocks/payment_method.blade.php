<div class="px-14 py-3 text-sm">
    @php
        $langMap = [
            'IT' => 'it',
            'ES' => 'es',
            'UK' => 'en',
            'GB' => 'en',
            'EN' => 'en',
            'FR' => 'fr',
        ];
        $locale = $langMap[strtoupper($client->country ?? $company->legal_country ?? 'IT')] ?? strtolower($company->legal_country ?? 'it');
    @endphp
    @if($pm->sdi_code == 'MP05')
      <p><strong>{{ __('invoices.Metodo di pagamento', [], $locale) }}:</strong> {{ $pm->type }}</p>
      <p><strong>{{ __('invoices.Banca', [], $locale) }}:</strong> {{ $pm->name }}</p>
      <p><strong>{{ __('invoices.IBAN', [], $locale) }}:</strong> {{ $pm->iban }}</p>
    @elseif($pm->sdi_code == 'MP08')
      <p><strong>{{ __('invoices.Metodo di pagamento', [], $locale) }}:</strong> {{ $pm->type }}</p>
    @endif
  </div>