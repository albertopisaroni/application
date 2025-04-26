<x-app-layout>
<div class="max-w-3xl mx-auto py-10">
    <h1 class="text-2xl font-bold mb-6">Modifica Cliente</h1>

    <form method="POST" action="{{ route('contatti.clienti.update', $client) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block mb-1 font-medium">Nome</label>
            <input type="text" name="name" value="{{ old('name', $client->name) }}" class="w-full border rounded px-3 py-2" required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 font-medium">Indirizzo</label>
                <input type="text" name="address" value="{{ old('address', $client->address) }}" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block mb-1 font-medium">CAP</label>
                <input type="text" name="cap" value="{{ old('cap', $client->cap) }}" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 font-medium">Città</label>
                <input type="text" name="city" value="{{ old('city', $client->city) }}" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block mb-1 font-medium">Provincia</label>
                <input type="text" name="province" value="{{ old('province', $client->province) }}" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block mb-1 font-medium">Nazione</label>
            <input type="text" name="country" value="{{ old('country', $client->country ?? 'IT') }}" class="w-full border rounded px-3 py-2">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 font-medium">P.IVA</label>
                <input type="text" name="piva" value="{{ old('piva', $client->piva) }}" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block mb-1 font-medium">Codice SDI</label>
                <input type="text" name="sdi" value="{{ old('sdi', $client->sdi) }}" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 font-medium">PEC</label>
                <input type="email" name="pec" value="{{ old('pec', $client->pec) }}" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block mb-1 font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email', $client->email) }}" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block mb-1 font-medium">Telefono</label>
            <input type="text" name="phone" value="{{ old('phone', $client->phone) }}" class="w-full border rounded px-3 py-2">
        </div>

        <div class="text-right">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Salva modifiche</button>
        </div>
    </form>
</div>
</x-app-layout>