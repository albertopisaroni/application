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
<td class="border-b-2 pb-3 pl-3 font-bold " style="border-color: #0d172b; color: #0d172b;" >{{ __('invoices.#', [], $locale) }}</td>
<td class="border-b-2 pb-3 pl-2 font-bold " style="border-color: #0d172b; color: #0d172b;" >{{ __('invoices.Dettaglio', [], $locale) }}</td>
<td class="border-b-2 pb-3 pl-2 text-right font-bold " style="border-color: #0d172b; color: #0d172b;" >{{ __('invoices.Q.tà', [], $locale) }}</td>
<td class="border-b-2 pb-3 pl-2 text-right font-bold " style="border-color: #0d172b; color: #0d172b;" >{{ __('invoices.Prezzo unitario', [], $locale) }}</td>