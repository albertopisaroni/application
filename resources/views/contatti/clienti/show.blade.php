<x-app-layout>
    <div class="max-w-4xl mx-auto py-10">
        <h1 class="text-2xl font-bold mb-6">Dettagli Cliente</h1>

        <div class="bg-white p-6 rounded shadow">
            <p class="mb-2"><strong>Nome:</strong> {{ $client->name }}</p>
            <p class="mb-2"><strong>Indirizzo:</strong> {{ $client->address }}</p>
            <p class="mb-2"><strong>CAP:</strong> {{ $client->cap }}</p>
            <p class="mb-2"><strong>Città:</strong> {{ $client->city }}</p>
            <p class="mb-2"><strong>Provincia:</strong> {{ $client->province }}</p>
            <p class="mb-2"><strong>Nazione:</strong> {{ $client->country }}</p>
            <p class="mb-2"><strong>P.IVA:</strong> {{ $client->piva }}</p>
            <p class="mb-2"><strong>Codice SDI:</strong> {{ $client->sdi }}</p>
            <p class="mb-2"><strong>PEC:</strong> {{ $client->pec }}</p>
            <p class="mb-2"><strong>Email:</strong> {{ $client->email }}</p>
            <p class="mb-2"><strong>Telefono:</strong> {{ $client->phone }}</p>
        </div>

        <div class="mt-8">
            <a href="{{ route('contatti.clienti.edit', $client) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Modifica Cliente</a>
        </div>

        <hr class="my-8">

        <h2 class="text-xl font-semibold mb-4">Contatti associati</h2>

        <table class="w-full table-auto">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">Nome</th>
                    <th class="py-2">Email</th>
                    <th class="py-2">Telefono</th>
                </tr>
            </thead>
            <tbody>
                @foreach($client->contacts as $contact)
                    <tr class="border-b">
                        <td class="py-2">{{ $contact->name }}</td>
                        <td class="py-2">{{ $contact->email }}</td>
                        <td class="py-2">{{ $contact->phone }}</td>
                        <td class="py-2">
                            <a href="{{ route('contatti.clienti.contact.edit', $contact) }}" class="text-blue-600 hover:underline">Modifica</a>
                            <form method="POST" action="{{ route('contatti.clienti.contact.destroy', $contact) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Elimina</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <hr class="my-8">
        <h2 class="text-xl font-semibold mb-4">Aggiungi contatto</h2>

        <form method="POST" action="{{ route('contatti.clienti.contact.store', $client) }}" class="grid grid-cols-2 gap-4 bg-white p-6 rounded shadow">
            @csrf
            <div>
                <label class="block font-medium mb-1">Nome</label>
                <input type="text" name="name" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Cognome</label>
                <input type="text" name="surname" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Email</label>
                <input type="email" name="email" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Telefono</label>
                <input type="text" name="phone" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Ruolo</label>
                <input type="text" name="role" class="w-full border rounded px-3 py-2">
            </div>
            <div class="col-span-2 flex items-center space-x-4 mt-4">
                <label><input type="checkbox" name="receives_invoice_copy" value="1" checked> Copia fatture</label>
                <label><input type="checkbox" name="receives_notifications" value="1" checked> Notifiche</label>
            </div>
            <div class="col-span-2 text-right mt-4">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Aggiungi contatto</button>
            </div>
        </form>
    </div>
</x-app-layout>
