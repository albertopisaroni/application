<x-app-layout>
<div class="max-w-5xl mx-auto py-10">
    <h1 class="text-2xl font-bold mb-6">Gestione Azienda</h1>

    {{-- INFO COMPANY --}}
    <div class="mb-8">
        <h2 class="text-xl font-semibold mb-2">Dati azienda</h2>
        <div class="bg-white p-4 rounded shadow">
            <p><strong>Nome:</strong> {{ $company->name }}</p>
            <p><strong>P.IVA:</strong> {{ $company->piva }}</p>
            <p><strong>Codice REA:</strong> {{ $company->tax_code }}</p>
        </div>
    </div>

    {{-- UTENTI ASSOCIATI --}}
    <div class="mb-8">
    <h2 class="text-xl font-bold mb-4">Utenti associati</h2>

<table class="w-full mb-4 border">
    <thead>
        <tr>
            <th class="text-left p-2 border">Nome</th>
            <th class="text-left p-2 border">Email</th>
            <th class="text-left p-2 border">Ruolo</th>
            <th class="text-left p-2 border">Azioni</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($company->users as $user)
            <tr>
                <td class="p-2 border">{{ $user->name }}</td>
                <td class="p-2 border">{{ $user->email }}</td>
                <td class="p-2 border">{{ $user->pivot->role }}</td>
                <td class="p-2 border">
                    @if ($user->id !== auth()->id())
                        <form method="POST" action="{{ route('company.users.remove', $user->id) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600">Rimuovi</button>
                        </form>
                    @else
                        <span class="text-gray-400 italic">Non rimuovibile</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<hr class="my-6">

<h2 class="text-xl font-bold mb-2">Aggiungi utente</h2>

<form method="POST" action="{{ route('company.users.add') }}" class="space-y-2 flex">
    @csrf
    <div class="mr-2">
        <label>Nome</label>
        <input type="name" name="name" class="border p-2 w-full" required>
    </div>
    <div class="mx-2">
        <label>Cognome</label>
        <input type="surname" name="surname" class="border p-2 w-full" required>
    </div>
    <div class="ml-2">
        <label>Email utente</label>
        <input type="email" name="email" class="border p-2 w-full" required>
    </div>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Aggiungi</button>
</form>
    </div>

    {{-- TOKEN API --}}
    <div>
        <h2 class="text-xl font-semibold mb-2">Token API</h2>
        <div class="bg-white p-4 rounded shadow mb-4">
            <form method="POST" action="{{ route('company.tokens.store', $company) }}">
                @csrf
                <div class="grid grid-cols-2 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium mb-1">Nome token</label>
                        <input type="text" name="name" class="w-full border rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Scadenza</label>
                        <select name="expires_in" class="w-full border rounded px-3 py-2">
                            <option value="30">30 giorni</option>
                            <option value="60">60 giorni</option>
                            <option value="90">90 giorni</option>
                            <option value="120">120 giorni</option>
                            <option value="240">240 giorni</option>
                            <option value="365">1 anno</option>
                            <option value="730">2 anni</option>
                            <option value="never">Mai</option>
                        </select>
                    </div>
                    <div class="col-span-2 text-right">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Crea token</button>
                    </div>
                </div>
            </form>
        </div>

        @if(session('token'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                Token creato: <code>{{ session('token') }}</code><br>
                <small>⚠️ Salvalo subito, non sarà più visibile.</small>
            </div>
        @endif

        <div class="bg-white p-4 rounded shadow">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="py-2">Nome</th>
                        <th class="py-2">Scadenza</th>
                        <th class="py-2">Creato il</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($company->apiTokens as $token)
                        <tr class="border-b">
                            <td class="py-2">{{ $token->name }}</td>
                            <td class="py-2">{{ $token->expires_at ? $token->expires_at->format('d/m/Y') : 'Mai' }}</td>
                            <td class="py-2">{{ $token->created_at->format('d/m/Y') }}</td>
                            <td class="py-2">
                                <form method="POST" action="{{ route('company.tokens.delete', $token->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 text-sm hover:underline">Elimina</button>
                                </form>
                                @if ($token->expires_at && $token->expires_at->isPast())
                                    <form method="POST" action="{{ route('company.tokens.renew', $token->id) }}" class="inline">
                                        @csrf
                                        <button class="text-blue-600 text-sm hover:underline">Rinnova</button>
                                    </form>
                                @endif
                            </td>

                            <td class="py-2">
                                @if (session('token') && hash('sha256', session('token')) === $token->token)
                                    <code>{{ session('token') }}</code>
                                    <small class="text-gray-500">(mostrato solo una volta)</small>
                                @else
                                    <code>****{{ substr($token->token, -4) }}</code>
                                @endif
                            </td>

                            
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-app-layout>