<div class="px-14 pt-8 text-sm text-neutral-700">
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
    <p style="color: #0d172b" class="font-bold">{{ __('invoices.Intestazione', [], $locale) }}:</p>
    <p>{{ $header_notes }}</p>
</div>