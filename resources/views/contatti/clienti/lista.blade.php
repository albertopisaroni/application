<x-app-layout>
<div class="max-w-5xl mx-auto py-10">
    <h1 class="text-2xl font-bold mb-6">Tutti i clienti</h1>

    <a wire:navigate href="{{ route('contatti.clienti.nuovo') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">+ Aggiungi cliente</a>

    <table class="w-full mt-6 border">
        <thead>
            <tr>
                <th class="text-left p-2 border">Nome</th>
                <th class="text-left p-2 border">P.IVA</th>
                <th class="text-left p-2 border">Azioni</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clients as $client)
            <tr>
                <td class="p-2 border">{{ $client->name }}</td>
                <td class="p-2 border">{{ $client->piva }}</td>
                <td class="p-2 border">
                    <a href="{{ route('contatti.clienti.show', $client) }}" class="text-blue-600 hover:underline">Visualizza</a>
                </td>
                <td class="p-2 border flex space-x-4">
                    <a href="{{ route('contatti.clienti.show', $client) }}" class="text-blue-600 hover:underline">Visualizza</a>

                    <form method="POST" action="{{ route('contatti.clienti.hide', $client) }}">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="text-red-600 hover:underline"
                            onclick="return confirm('Sei sicuro di voler nascondere questo cliente?')">
                            Nascondi
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
</x-app-layout>
