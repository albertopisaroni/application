<div>
    <div class="px-2">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-normal">Clienti</h1>

            <div class="flex items-center gap-x-4">
                @if ($search)
                <button wire:click="resetFilters" class="items-center gap-x-2 flex bg-[#e8e8e8] pr-4 pl-3 py-2 text-sm rounded-[4px] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>                      
                        Elimina filtri
                    </button>
                @endif

                <a href="{{ route('contatti.clienti.nuovo') }}" wire:navigate class="items-center gap-x-2 flex bg-black text-white px-4 py-2 text-sm rounded-[4px] transition">
                    <svg width="11" height="17" viewBox="0 0 11 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_569_5702)">
                        <path d="M6.50062 0.5C6.67973 0.586454 6.79013 0.711577 6.75418 0.923154L6.02499 6.493H10.5442C10.675 6.493 10.8805 6.84542 10.7466 6.97557L5.44883 16.3243C5.18286 16.694 4.733 16.4283 4.81858 16.014L5.39092 10.5092H0.93466C0.832853 10.5092 0.620968 10.214 0.704004 10.0926L6.06285 0.772881C6.13188 0.649959 6.21269 0.560675 6.34218 0.5H6.50062Z" fill="white"/>
                        </g>
                        <defs>
                        <clipPath id="clip0_569_5702">
                        <rect width="10.1053" height="16" fill="white" transform="translate(0.684784 0.5)"/>
                        </clipPath>
                        </defs>
                    </svg>
                    Aggiungi cliente
                </a>
            </div>
        </div>

        <div class="relative flex mb-6 bg-[#f5f5f5] rounded-[4px] items-center pl-4 pr-2">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                <mask id="mask0_569_5714" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="16" height="16">
                <path d="M0 0H16V16H0V0Z" fill="white"/>
                </mask>
                <g mask="url(#mask0_569_5714)">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M11.8875 11.2198L8.775 8.09977C9.4425 7.25977 9.84 6.20227 9.84 5.05477C9.84 2.33227 7.635 0.134766 4.92 0.134766C2.205 0.134766 0 2.33227 0 5.05477C0 7.77727 2.205 9.97477 4.92 9.97477C6.21 9.97477 7.38 9.47977 8.2575 8.66227L11.355 11.7523C11.505 11.9023 11.745 11.9023 11.8875 11.7523C12.0375 11.6098 12.0375 11.3698 11.8875 11.2198ZM4.92 9.21727C2.6175 9.21727 0.7575 7.34977 0.7575 5.05477C0.7575 2.75977 2.6175 0.892266 4.92 0.892266C7.2225 0.892266 9.0825 2.75227 9.0825 5.05477C9.0825 7.35727 7.2225 9.21727 4.92 9.21727Z" fill="#050505"/>
                </g>
            </svg>
            <input wire:model.debounce.live.500ms="search" type="text" placeholder="Cerca per nome cliente, P.IVA o email" class="bg-[#f5f5f5] rounded-[4px] border-0 ring-0 focus:ring-0 pr-4 py-2 w-full text-sm">
            <div class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400 bg-white border border-gray-300 rounded px-1 py-0.5 pointer-events-none">
                ⌘K
            </div>
        </div>

        <div class="mb-3 text-sm text-gray-600 flex items-center gap-x-8">
            <button class="text-[#050505] hover:font-semibold focus:outline-none font-semibold">
                Totale: {{ $totalClients }}
            </button>
        
            <button class="hover:font-semibold focus:outline-none">
                Con contatti: {{ $clientsWithContacts }}
            </button>

            <button class="hover:font-semibold focus:outline-none">
                Senza contatti: {{ $clientsWithoutContacts }}
            </button>
                
            <div class="bg-[#dcf3fd] text-[#616161] rounded-[6.75px] px-4 py-2 text-sm flex gap-x-1 items-center">
                <svg width="6" height="15" viewBox="0 0 6 15" fill="none" xmlns="http://www.w3.org/2000/svg" class="mr-1">
                    <g filter="url(#filter0_d_913_5566)">
                    <path d="M3.924 9.39754H1.92491L1.50494 1.3844H4.34397L3.924 9.39754ZM1.47134 12.203C1.47134 11.6878 1.61133 11.3294 1.89131 11.1278C2.1713 10.915 2.51288 10.8087 2.91605 10.8087C3.30803 10.8087 3.64401 10.915 3.924 11.1278C4.20398 11.3294 4.34397 11.6878 4.34397 12.203C4.34397 12.6957 4.20398 13.0541 3.924 13.2781C3.64401 13.4909 3.30803 13.5973 2.91605 13.5973C2.51288 13.5973 2.1713 13.4909 1.89131 13.2781C1.61133 13.0541 1.47134 12.6957 1.47134 12.203Z" fill="#616161"/>
                    </g>
                    <defs>
                    <filter id="filter0_d_913_5566" x="0.771385" y="0.684806" width="4.27254" height="13.6128" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                    <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                    <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                    <feOffset/>
                    <feGaussianBlur stdDeviation="0.34998"/>
                    <feComposite in2="hardAlpha" operator="out"/>
                    <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.01 0"/>
                    <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_913_5566"/>
                    <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_913_5566" result="shape"/>
                    </filter>
                    </defs>
                </svg>
                Hai <strong>{{ $totalClients }} clienti</strong> totali, di cui <strong>{{ $clientsWithContacts }}</strong> con contatti associati
            </div>
        </div>

        <div class="">
            <table class="w-full text-sm text-left border-collapse">
                <thead>
                    <tr class="text-[#616161] text-xs border-b">
                        <th class="py-2 pl-2 pr-4 font-normal">Cliente</th>
                        <th class="py-2 px-4 font-normal">P.IVA</th>
                        <th class="py-2 px-4 font-normal">Email</th>
                        <th class="py-2 px-4 font-normal">Città</th>
                        <th class="py-2 px-4 font-normal">Contatti</th>
                        <th class="py-2 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($clients as $client)
                        <tr class="hover:bg-[#f5f5f5] bg-white group transition-all duration-200">
                            <td class="whitespace-nowrap py-4 pl-2 pr-4">
                                <div class="flex items-center gap-2">
                                    <img src="{{ $client->logo }}" alt="{{ $client->name }}" class="w-8 h-8 rounded-full object-cover" />
                                    <div class="flex-1 min-w-0">
                                      <div class="font-normal text-gray-900 truncate">
                                        {{ $client->name ?? '' }}
                                      </div>
                                      <div class="text-xs text-[#616161] lowercase truncate">
                                        {{ $client->domain ?? '' }}
                                      </div>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap py-4 px-4 font-normal text-gray-800">
                                {{ $client->piva ?? '–' }}
                            </td>
                            <td class="whitespace-nowrap py-4 px-4 text-gray-700">
                                {{ $client->primaryContact?->email ?? '–' }}
                            </td>
                            <td class="whitespace-nowrap py-4 px-4 text-gray-700">
                                {{ $client->city ?? '–' }}
                            </td>
                            <td class="whitespace-nowrap py-4 px-4 text-gray-700">
                                {{ $client->contacts->count() }}
                            </td>
                            <td class="whitespace-nowrap py-4 px-4 text-right">
                                <div class="flex justify-end items-center gap-x-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200 relative">                        
                                    
                                    <div class="relative" x-data="{ open: false }">
                                        <button @click="open = !open" class="p-1 hover:bg-gray-100 rounded">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <rect x="0.392857" y="0.392857" width="19.2143" height="19.2143" rx="2.75" stroke="#AD96FF" stroke-width="0.785714"/>
                                                <path d="M5.74408 10H5.75063M10 10H10.0066M14.2494 10H14.256" stroke="#AD96FF" stroke-width="2.35714" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                        
                                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border">
                                            <div class="py-1">
                                                <a href="{{ route('contatti.clienti.show', $client) }}" wire:navigate @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <div class="flex items-center gap-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                                            <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                                                            <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" />
                                                        </svg>
                                                        Visualizza
                                                    </div>
                                                </a>
                                                <a href="{{ route('contatti.clienti.edit', $client) }}" wire:navigate @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <div class="flex items-center gap-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                                            <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                                                            <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
                                                        </svg>
                                                        Modifica
                                                    </div>
                                                </a>
                                                <button wire:click="showMergeModal({{ $client->id }})" 
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <div class="flex items-center gap-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                                            <path fill-rule="evenodd" d="M8.25 3.75A.75.75 0 0 1 9 3h6a.75.75 0 0 1 .75.75v6a.75.75 0 0 1-1.5 0V5.56L4.06 15.06a.75.75 0 0 1-1.06-1.06L13.44 4.5H9a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                                        </svg>
                                                        Unisci cliente
                                                    </div>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $clients->links() }}
        </div>
    </div>

    <!-- Merge Modal -->
    @if($mergeModalVisible)
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
                    
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                        <p class="text-sm text-blue-800">
                            <strong>Nota:</strong> I contatti associati ai clienti verranno automaticamente uniti al cliente di destinazione. 
                            Per i campi Stripe, quando si seleziona un Customer ID, verrà automaticamente copiato anche l'Account ID corrispondente.
                        </p>
                    </div>
                    
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
                            'stripe_customer_id' => 'Stripe Customer ID'
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
</div> 