<x-guest-layout>
    <div class="max-w-md mx-auto text-center mt-20 space-y-8">

        <h1 class="text-2xl font-bold">Ciao {{ $registration->name ?? '!' }}</h1>
        <p class="text-gray-500">Vuoi aprire una partita IVA o ne hai già una?</p>

        {{-- Bottone: Apri nuova partita IVA --}}
        <a href="{{ route('registration.step.4', ['uuid' => $registration->uuid]) }}"
           class="block w-full border border-black text-black font-medium rounded-xl py-4 text-lg hover:scale-[1.01] transition">
            Apri una nuova partita IVA
        </a>

        {{-- Avatar + Oppure --}}
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center">
                <div class="bg-white px-4 text-sm text-gray-400 font-medium flex items-center gap-2">
                    <div class="bg-green-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs shadow-md">
                        {{ strtoupper(substr($registration->name, 0, 1)) }}
                    </div>
                    oppure
                </div>
            </div>
        </div>

        {{-- Bottone: Ho già una partita IVA --}}
        <a href="{{ route('registration.step.4', ['uuid' => $registration->uuid]) }}"
           class="block w-full bg-[#b499ff] text-white font-medium rounded-xl py-4 text-lg hover:bg-[#a688f2] transition">
            Ho già una partita IVA →
        </a>

    </div>
</x-guest-layout>