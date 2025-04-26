<x-guest-layout>
    <div class="text-center py-20">
        <h1 class="text-2xl font-bold mb-6">Benvenuto su Newo!</h1>
        <p class="mb-10">Per iniziare, scegli un'opzione:</p>

        <div class="space-y-4">
            <a href="{{ route('companies.create') }}" class="mr-1 bg-gray-200 text-gray-800 px-6 py-3 rounded shadow hover:bg-gray-300">
            ➕ Crea nuova Partita IVA
            </a>

            <a href="{{ route('companies.import') }}" class="ml-1 bg-gray-200 text-gray-800 px-6 py-3 rounded shadow hover:bg-gray-300">
                ➕ Inserisci Partita IVA esistente
            </a>
        </div>
    </div>
</x-guest-layout>