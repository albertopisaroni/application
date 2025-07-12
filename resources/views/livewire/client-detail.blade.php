<div class="max-w-4xl mx-auto py-10">
        <h1 class="text-2xl font-bold mb-6">Dettagli Cliente</h1>

        <!-- Test button to verify component is working -->
        <button wire:click="testMethod" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 mb-4">
            Test Component
        </button>

        <div class="bg-white p-6 rounded shadow">
            <p class="mb-2"><strong>Nome:</strong> {{ $client->name }}</p>
            <p class="mb-2"><strong>Dominio:</strong> {{ $client->domain ?: 'Non impostato' }}</p>
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

        <div class="mt-8 flex gap-3">
            <a href="{{ route('contatti.clienti.edit', $client) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Modifica Cliente</a>
            <button wire:click="showMergeModal({{ $client->id }})" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                    <path fill-rule="evenodd" d="M8.25 3.75A.75.75 0 0 1 9 3h6a.75.75 0 0 1 .75.75v6a.75.75 0 0 1-1.5 0V5.56L4.06 15.06a.75.75 0 0 1-1.06-1.06L13.44 4.5H9a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                </svg>
                Unisci Cliente
            </button>
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

    <!-- Merge Modal -->
    @if($showMergeModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="merge-modal">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Unisci Cliente</h3>
                    <button wire:click="closeMergeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                @if(!$targetClientId)
                <!-- Step 1: Select target client -->
                <div>
                    <h4 class="text-md font-medium mb-4">Seleziona il cliente da unire</h4>
                    
                    <div class="mb-4">
                        <input wire:model.live="mergeSearch" type="text" placeholder="Cerca per nome, P.IVA, email..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="max-h-60 overflow-y-auto">
                        @foreach($suggestedClients as $client)
                        <div class="flex items-center justify-between p-3 border-b hover:bg-gray-50 cursor-pointer" 
                             wire:click="selectTargetClient({{ $client->id }})">
                            <div class="flex items-center gap-3">
                                <img src="{{ $client->logo }}" alt="{{ $client->name }}" class="w-8 h-8 rounded-full object-cover" />
                                <div>
                                    <div class="font-medium">{{ $client->name }}</div>
                                    <div class="text-sm text-gray-600">
                                        {{ $client->piva ? 'P.IVA: ' . $client->piva : '' }}
                                        {{ $client->primaryContact?->email ? ' • ' . $client->primaryContact->email : '' }}
                                    </div>
                                </div>
                            </div>
                            <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Unisci
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <!-- Step 2: Compare and merge -->
                <div>
                    <h4 class="text-md font-medium mb-4">Confronta e unisci i dati</h4>
                    
                    @php
                        $sourceClient = \App\Models\Client::find($sourceClientId);
                        $targetClient = \App\Models\Client::find($targetClientId);
                        $fields = [
                            'name' => 'Nome',
                            'domain' => 'Dominio',
                            'address' => 'Indirizzo',
                            'cap' => 'CAP',
                            'city' => 'Città',
                            'province' => 'Provincia',
                            'country' => 'Nazione',
                            'piva' => 'P.IVA',
                            'sdi' => 'Codice SDI',
                            'pec' => 'PEC',
                            'email' => 'Email',
                            'phone' => 'Telefono'
                        ];
                    @endphp

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-2 px-4 bg-blue-100 text-blue-800">Cliente A</th>
                                    <th class="text-left py-2 px-4 bg-red-100 text-red-800">Cliente B</th>
                                    <th class="text-left py-2 px-4 bg-green-100 text-green-800">Da mantenere</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fields as $field => $label)
                                <tr class="border-b">
                                    <td class="py-3 px-4 {{ !empty($sourceClient->$field) ? 'bg-blue-50' : 'bg-gray-50' }}">
                                        <div class="font-medium">{{ $label }}</div>
                                        <div class="text-sm text-gray-600">{{ $sourceClient->$field ?: '—' }}</div>
                                    </td>
                                    <td class="py-3 px-4 {{ !empty($targetClient->$field) ? 'bg-red-50' : 'bg-gray-50' }}">
                                        <div class="font-medium">{{ $label }}</div>
                                        <div class="text-sm text-gray-600">{{ $targetClient->$field ?: '—' }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex gap-3">
                                            <label class="flex items-center">
                                                <input type="radio" name="merge_{{ $field }}" value="source" 
                                                       wire:model="selectedMergeData.{{ $field }}"
                                                       class="mr-2">
                                                <span class="text-blue-600">A</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" name="merge_{{ $field }}" value="target" 
                                                       wire:model="selectedMergeData.{{ $field }}"
                                                       class="mr-2">
                                                <span class="text-red-600">B</span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button wire:click="closeMergeModal" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                            Annulla
                        </button>
                        <button wire:click="mergeClients" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Confronta e unisci
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif 