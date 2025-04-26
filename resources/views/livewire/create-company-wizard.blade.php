<div class="max-w-xl mx-auto py-12">
    @if ($step === 1)
        <div>
            <h2 class="text-xl font-semibold mb-4">1. Nome e Cognome</h2>

            <div class="mb-4">
                <input type="text" wire:model="first_name" placeholder="Nome" class="w-full rounded border-gray-300">
                @error('first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <input type="text" wire:model="last_name" placeholder="Cognome" class="w-full rounded border-gray-300">
                @error('last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <button wire:click="nextStep" class="bg-blue-600 text-white px-4 py-2 rounded">
                Avanti
            </button>
        </div>

    @elseif ($step === 2)
        <div>
            <h2 class="text-xl font-semibold mb-4">2. Di cosa ti occuperai?</h2>

            <div class="mb-4">
                <textarea wire:model="business_description" placeholder="Descrivi brevemente l’attività che svolgerai..." rows="5" class="w-full rounded border-gray-300"></textarea>
                @error('business_description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-between">
                <button wire:click="previousStep" class="bg-gray-200 text-gray-700 px-4 py-2 rounded">
                    Indietro
                </button>

                <button wire:click="nextStep" class="bg-blue-600 text-white px-4 py-2 rounded">
                    Avanti
                </button>
            </div>
        </div>
    @elseif ($step === 3)
        <div>
            <h2 class="text-xl font-semibold mb-4">3. Luogo e data di nascita</h2>

            <div class="mb-4">
                <label class="block font-semibold mb-1">Data di nascita</label>
                <input type="date" wire:model="birth_date" class="w-full rounded border-gray-300">
                @error('birth_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4 relative">
                <label class="block font-semibold mb-1">Comune di nascita</label>

                <input type="text"
                    wire:model.live.debounce.500ms="birth_city"
                    placeholder="Comune di nascita"
                    class="w-full rounded border-gray-300"
                    autocomplete="off">

                @error('birth_city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                @if (!empty($birthCitySuggestions))
                    <ul class="absolute bg-white border rounded shadow w-full mt-1 z-10">
                    @foreach ($birthCitySuggestions as $suggestion)
                        <li wire:click='selectBirthCity(
                                "{{ $suggestion["description"] }}",
                                "{{ $suggestion["place_id"] }}",
                                @json($suggestion["terms"])
                            )'
                            class="px-3 py-2 hover:bg-gray-100 cursor-pointer">
                            {{ $suggestion['description'] }}
                        </li>
                    @endforeach
                    </ul>
                @endif
            </div>
            
            

            <div class="flex justify-between">
                <button wire:click="previousStep" class="bg-gray-200 text-gray-700 px-4 py-2 rounded">
                    Indietro
                </button>

                <button wire:click="nextStep" class="bg-blue-600 text-white px-4 py-2 rounded">
                    Avanti
                </button>
            </div>
        </div>
    @elseif ($step === 4)
        <div>
            <h2 class="text-xl font-semibold mb-4">4. Residenza</h2>

            <div class="mb-4 relative">
                <label class="block font-semibold mb-1">Indirizzo di residenza</label>

                <input type="text"
                    wire:model.live.debounce.500ms="residence_city"
                    placeholder="Indirizzo di residenza"
                    class="w-full rounded border-gray-300"
                    autocomplete="off">

                @error('residence_city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                @if (!empty($residenceCitySuggestions))
                    <ul class="absolute bg-white border rounded shadow w-full mt-1 z-10">
                    @foreach ($residenceCitySuggestions as $suggestion)
                        <li wire:click='selectResidenceCity(
                                "{{ $suggestion["description"] }}",
                                "{{ $suggestion["place_id"] }}",
                                @json($suggestion["terms"])
                            )'
                            class="px-3 py-2 hover:bg-gray-100 cursor-pointer">
                            {{ $suggestion['description'] }}
                        </li>
                    @endforeach
                    </ul>
                @endif
            </div>
            
            

            <div class="flex justify-between">
                <button wire:click="previousStep" class="bg-gray-200 text-gray-700 px-4 py-2 rounded">
                    Indietro
                </button>

                <button wire:click="nextStep" class="bg-blue-600 text-white px-4 py-2 rounded">
                    Avanti
                </button>
            </div>
        </div>

    @elseif ($step === 5)
        <div>
            <h2 class="text-xl font-semibold mb-4">5. Contatti</h2>

            <div class="mb-4">
                <label class="block font-semibold mb-1">Email personale</label>
                <input type="email" wire:model="personal_email" placeholder="Email personale" class="w-full rounded border-gray-300">
                @error('personal_email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-1">Telefono</label>
                <input type="text" wire:model="personal_phone" placeholder="Telefono" class="w-full rounded border-gray-300">
                @error('personal_phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-between">
                <button wire:click="previousStep" class="bg-gray-200 text-gray-700 px-4 py-2 rounded">
                    Indietro
                </button>
                <button wire:click="nextStep" class="bg-blue-600 text-white px-4 py-2 rounded">
                    Avanti
                </button>
            </div>
        </div>
    @elseif ($step === 6)
        <div>
            <h2 class="text-xl font-semibold mb-4">6. Codici ATECO</h2>
            <p class="mb-4">I seguenti codici sono stati suggeriti automaticamente dalla descrizione dell’attività. Puoi rimuovere quelli non pertinenti o aggiungerne altri manualmente dalla lista.</p>
            
            <!-- Suggerimenti automatici da ChatGPT -->
            <div class="mb-4">
                <label class="block font-semibold mb-1">Suggerimenti automatici</label>
                @if(count($ateco_suggestions))
                    <ul class="list-disc ml-4">
                    @foreach ($ateco_suggestions as $sugg)
    @if(isset($sugg['code']) && isset($sugg['description']))
        <li class="flex justify-between items-center">
            <span>{{ $sugg['code'] }} - {{ $sugg['description'] }}</span>
            <button wire:click="removeAtecoCode('{{ $sugg['code'] }}')" class="text-red-500 ml-2">Rimuovi</button>
        </li>
    @else
        {{-- Se manca la chiave, logga per debug o ignora --}}
        @php
            logger('Suggerimento ATECO mancante chiave', $sugg);
        @endphp
    @endif
@endforeach
                    </ul>
                @else
                    <p class="text-gray-600">Nessun suggerimento disponibile.</p>
                @endif
            </div>
            
            <!-- Campo per la ricerca manuale (autocomplete dal DB) -->
            <div class="mb-4">
                <label class="block font-semibold mb-1">Cerca e aggiungi codici ATECO</label>
                <input type="text" 
                    wire:model.live.debounce.500ms="ateco_query" 
                    placeholder="Digita codice o descrizione" 
                    class="w-full rounded border-gray-300">
                
                @if (!empty($ateco_manual_suggestions))
                    <ul class="bg-white border rounded shadow w-full mt-1 z-10">
                        @foreach ($ateco_manual_suggestions as $sugg)
                            <li wire:click="addAtecoFromManual('{{ $sugg['code'] }}')" class="px-3 py-2 hover:bg-gray-100 cursor-pointer">
                                {{ $sugg['code'] }} - {{ $sugg['description'] }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            
            <!-- Visualizzazione dei codici selezionati -->
            <div class="mb-4">
                <label class="block font-semibold">Codici selezionati</label>
                @if (count($selected_ateco_codes))
                    <ul class="list-disc ml-4">
                        @foreach ($selected_ateco_codes as $code)
                            <li class="flex justify-between items-center">
                                <span>
                                    {{ $code }} -
                                    @php
                                        // Recupera la descrizione dalla lista completa
                                        $atecoItem = collect($ateco_list)->firstWhere('code', $code);
                                    @endphp
                                    {{ $atecoItem ? $atecoItem['description'] : '' }}
                                </span>
                                <button wire:click="removeAtecoCode('{{ $code }}')" class="text-red-500 ml-2">Rimuovi</button>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-600">Nessun codice selezionato.</p>
                @endif
            </div>
            
            <div class="flex justify-between mt-4">
                <button wire:click="previousStep" class="bg-gray-200 text-gray-700 px-4 py-2 rounded">
                    Indietro
                </button>
                <button wire:click="nextStep" class="bg-blue-600 text-white px-4 py-2 rounded">
                    Finisci
                </button>
            </div>
        </div>
    @endif
</div>