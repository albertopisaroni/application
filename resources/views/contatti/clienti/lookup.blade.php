<x-app-layout>
    <div class="max-w-xl mx-auto py-10">
        <h1 class="text-2xl font-bold mb-6">Aggiungi Cliente tramite P.IVA</h1>

        <form method="POST" action="{{ route('contatti.clienti.nuovo.lookup') }}" class="bg-white p-6 rounded shadow space-y-4">
            @csrf
            <div>
                <label class="block font-medium mb-1">Partita IVA</label>
                <input type="text" name="piva" class="w-full border rounded px-3 py-2" required placeholder="es. IT01234567890">
            </div>
            <div class="text-right">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Recupera dati</button>
            </div>
        </form>
    </div>
</x-app-layout>