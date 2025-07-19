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
    <p class="font-bold">{{ __('invoices.Scadenze pagamento', [], $locale) }}:</p>
    @foreach($schedules as $schedule)
      <p>
        €{{ number_format($schedule['amount'], 2, ',', '.') }}
         – {{ \Carbon\Carbon::parse($schedule['date'] ?? $schedule['due_date'])->format('d/m/Y') }}
      </p>
    @endforeach
  </div>