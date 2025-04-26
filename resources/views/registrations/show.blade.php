<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6">
        <h2 class="text-xl font-bold mb-6">Dettaglio registrazione #{{ $registration->id }}</h2>

        @livewire('admin.registration-detail-table', ['registration' => $registration])
    </div>
</x-app-layout>