<div wire:poll.3s>
    <div class="relative h-4 bg-gray-200 rounded-full mb-6">
        <div class="absolute top-0 left-0 h-4 bg-purple-400 rounded-full transition-all duration-300"
             style="width: {{ $this->percentage }}%"></div>

        <div class="absolute top-1/2 transform -translate-y-1/2 right-0 translate-x-1/2 bg-purple-400 text-white text-sm font-semibold px-3 py-0.5 rounded-full shadow">
            {{ $this->currentStep }}/7
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4 mt-4 mb-6 text-sm text-gray-700">
        <div><strong>Tempo sulla pagina:</strong> {{ $registration->page_time }}s</div>
        <div><strong>Scroll:</strong> {{ $registration->scroll_time }}s / Bounce: {{ $registration->scroll_bounce }}</div>
        <div><strong>Movimenti mouse:</strong> {{ $registration->mouse_movement }}</div>
    </div>

    <div class="flex items-center gap-3 mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Profilo comportamentale:</h2>
        <span class="bg-purple-100 text-purple-800 text-sm px-3 py-1 rounded-full shadow-sm">
            @livewire('admin.edit-registration-field', ['registration' => $registration, 'field' => 'behavior_profile'], key($registration->id.'-behavior_profile2'))
        </span>
    </div>
    <div class="mb-6">
        
        <button
            wire:click="toggleOnboardingOptions"
            class="mt-6 bg-gradient-to-r from-purple-500 to-purple-700 hover:from-purple-600 hover:to-purple-800 text-white text-lg font-bold px-5 py-3 rounded shadow-lg">
            INIZIA ONBOARDING
        </button>

        @if($showOnboardingOptions)
            <div class="mt-4 flex flex-wrap gap-4 items-center">

                {{-- EMAIL --}}
                <button
                    wire:click="sendOnboardingEmail"
                    wire:loading.attr="disabled"
                    wire:target="sendOnboardingEmail"
                    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold px-4 py-2 rounded">
                    Invia link onboarding tramite email
                </button>

                {{-- SMS --}}
                @php
                    $remaining = $lastSmsSentAt ? max(0, 90 - (now()->timestamp - $lastSmsSentAt)) : 0;
                @endphp

                @if ($remaining > 0)
                    <button
                        disabled
                        class="bg-gray-300 text-gray-700 font-semibold px-4 py-2 rounded opacity-60 cursor-not-allowed">
                        Attendi {{ $remaining }}s per reinviare SMS
                    </button>
                @else
                    <button
                        wire:click="sendOnboardingSms"
                        wire:loading.attr="disabled"
                        wire:target="sendOnboardingSms"
                        class="bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 rounded">
                        Invia link onboarding tramite SMS
                    </button>
                @endif

                {{-- WHATSAPP --}}
                <button
                    wire:click="sendOnboardingWhatsapp"
                    wire:loading.attr="disabled"
                    wire:target="sendOnboardingWhatsapp"
                    class="bg-emerald-500 hover:bg-emerald-600 text-white font-semibold px-4 py-2 rounded">
                    Invia link onboarding via WhatsApp
                </button>

                {{-- Loader SMS --}}
                <div wire:loading.flex wire:target="sendOnboardingSms" class="items-center text-sm text-gray-500">
                    <svg class="animate-spin h-5 w-5 mr-2 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    Invio SMS in corso...
                </div>

                {{-- Loader EMAIL --}}
                <div wire:loading.flex wire:target="sendOnboardingEmail" class="items-center text-sm text-gray-500">
                    <svg class="animate-spin h-5 w-5 mr-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    Invio Email in corso...
                </div>

                {{-- Loader WHATSAPP --}}
                <div wire:loading.flex wire:target="sendOnboardingWhatsapp" class="items-center text-sm text-gray-500">
                    <svg class="animate-spin h-5 w-5 mr-2 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    Invio WhatsApp in corso...
                </div>

            </div>
        @endif

    </div>

    @if (session()->has('message'))
        <div class="mb-4 text-sm px-4 py-2 rounded font-medium
                    @if(Str::contains(session('message'), 'onboarding')) bg-yellow-100 text-yellow-800
                    @else bg-green-100 text-green-800 @endif">
            {{ session('message') }}
        </div>
    @endif

    <h3 class="text-lg font-bold mt-10 mb-3 text-gray-800">📇 Informazioni anagrafiche</h3>
    <table class="table-auto w-full text-sm border border-gray-200" wire:poll.3s>
        <tbody>
            @foreach($this->userFields as $key => $value)
                <tr class="border-t">
                    <td class="font-medium p-2 w-1/4">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                    <td class="p-2 w-3/4">
                        @livewire('admin.edit-registration-field', ['registration' => $registration, 'field' => $key], key($registration->id.'-'.$key))
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3 class="text-lg font-bold mt-10 mb-3 text-gray-800">📊 Dati di marketing e comportamento</h3>
    <table class="table-auto w-full text-sm border border-gray-200">
        <tbody>
            @foreach($this->marketingFields as $key => $value)
                <tr class="border-t">
                    <td class="font-medium p-2 w-1/4">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                    <td class="p-2 w-3/4">
                        @livewire('admin.edit-registration-field', ['registration' => $registration, 'field' => $key], key($registration->id.'-'.$key))
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>