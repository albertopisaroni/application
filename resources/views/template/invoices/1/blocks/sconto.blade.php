<tr>
    <td class="border-b p-3 w-full"></td>
    <td class="border-b p-3">
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
        <div class="whitespace-nowrap text-neutral-700">{{ __('invoices.Sconto', [], $locale) }}:</div>
    </td>
    <td class="border-b p-3 text-right">
        <div class="whitespace-nowrap text-neutral-700">€ {{ $sconto }}</div>
    </td>
</tr>