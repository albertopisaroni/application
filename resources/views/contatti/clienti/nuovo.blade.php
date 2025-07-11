<x-app-layout>
<div class="max-w-3xl mx-auto py-10">
    <h1 class="text-2xl font-bold mb-6">Aggiungi Cliente</h1>

    @if (session('autofill'))
        <div class="bg-yellow-100 text-yellow-800 p-3 rounded mb-4 text-sm">
            ✨ Dati precompilati automaticamente da OpenAPI.
        </div>
    @endif

    <form method="POST" action="{{ route('contatti.clienti.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block mb-1 font-medium">Nome</label>
            <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded px-3 py-2" required>
        </div>

        <div>
            <label class="block mb-1 font-medium">Dominio</label>
            <input type="text" name="domain" value="{{ old('domain') }}" class="w-full border rounded px-3 py-2" placeholder="esempio.com">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 font-medium">Indirizzo</label>
                <input type="text" name="address" value="{{ old('address') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block mb-1 font-medium">CAP</label>
                <input type="text" name="cap" value="{{ old('cap') }}" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 font-medium">Città</label>
                <input type="text" name="city" value="{{ old('city') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block mb-1 font-medium">Provincia</label>
                <input type="text" name="province" value="{{ old('province') }}" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block mb-1 font-medium">Nazione</label>
            <input type="text" name="country" value="{{ old('country', 'IT') }}" class="w-full border rounded px-3 py-2">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 font-medium">P.IVA</label>
                <input type="text" name="piva" value="{{ old('piva') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block mb-1 font-medium">Codice SDI</label>
                <input type="text" name="sdi" value="{{ old('sdi') }}" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block mb-1 font-medium">PEC</label>
            <input type="email" name="pec" value="{{ old('pec') }}" class="w-full border rounded px-3 py-2">
        </div>

        <div class="text-right mt-4">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Salva</button>
        </div>
    </form>

    <div class="mt-6">
        <a href="{{ route('contatti.clienti.nuovo.lookup') }}" class="text-sm text-blue-600 hover:underline">
            Oppure importa da P.IVA
        </a>
    </div>
</div>
</x-app-layout>