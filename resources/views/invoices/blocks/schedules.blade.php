<div class="px-14 py-3 text-sm">
    <p class="font-bold">Scadenze pagamento:</p>
    @foreach($schedules as $schedule)
      <p>
        €{{ number_format($schedule['amount'], 2, ',', '.') }}
         – {{ \Carbon\Carbon::parse($schedule['date'] ?? $schedule['due_date'])->format('d/m/Y') }}
      </p>
    @endforeach
  </div>