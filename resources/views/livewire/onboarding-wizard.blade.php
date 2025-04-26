<div class="max-w-md mx-auto mt-10">

    <div class="text-xs text-center text-gray-400 mb-4">STEP ATTUALE: {{ $step }}</div>

@if ($step > 2)
    <div class="mb-6 max-w-md mx-auto">
        <button wire:click="goBack"
                class="text-sm text-gray-500 hover:text-black flex items-center gap-1 transition">
            ← Indietro
        </button>
    </div>
@endif

@if ($step === 1)
        <div class="text-center space-y-6">
            <h1 class="text-2xl font-bold">Ciao {{ $name }}</h1>
            <p class="text-gray-500">Vuoi aprire una partita IVA o ne hai già una?</p>

            <a href="#" class="block w-full border border-black text-black rounded-xl py-4 text-lg">Apri una nuova partita IVA</a>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center">
                    <div class="bg-white px-4 text-sm text-gray-400 font-medium flex items-center gap-2">
                        <div class="bg-green-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs shadow-md">
                            {{ strtoupper(substr($name, 0, 1)) }}
                        </div>
                        oppure
                    </div>
                </div>
            </div>

            <button wire:click="goToExistingPiva"
                    class="block w-full bg-[#b499ff] text-white rounded-xl py-4 text-lg hover:bg-[#a688f2] transition">
                Ho già una partita IVA →
            </button>
        </div>
        @elseif ($step === 2)
            <div class="max-w-md mx-auto mt-20 space-y-6 text-center">
                <h1 class="text-2xl font-bold">Ottimo {{ $name }}</h1>
                <p class="text-gray-500">Inserisci la tua partita IVA o Ragione sociale</p>

                <form wire:submit.prevent="searchCompany" class="flex items-center gap-2 justify-center">
                    <div class="w-full max-w-xs">
                        <input type="text" wire:model.defer="searchQuery"
                            placeholder="Holding Shake, 03666547282"
                            class="rounded-xl border border-black px-4 py-3 text-center placeholder-gray-400 w-full">
                        @error('searchQuery')
                            <p class="text-sm text-red-600 mt-2 text-left">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled"
                            class="bg-black text-white rounded-lg px-6 py-3 hover:bg-black/80 transition">
                        Cerca
                    </button>
                </form>

                <div wire:loading wire:target="searchCompany" class="mt-4 text-sm text-gray-500 flex justify-center items-center gap-2">
                    <svg class="animate-spin h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8v8H4z">
                        </path>
                    </svg>
                    Cerchiamo la tua azienda...
                </div>

                <p class="text-sm text-gray-600">Hai problemi a trovare la tua P.IVA?</p>
            </div>
            @elseif ($step === 3)
                <div class="max-w-md mx-auto mt-20 space-y-6 text-center">
                    <h1 class="text-2xl font-bold">Controlliamo un secondo</h1>
                    <p class="text-gray-500">Abbiamo recuperato queste informazioni. Verifica che siano corrette prima di continuare.</p>

                    <div class="space-y-2 text-left font-medium">
                        <div class="text-black">{{ $companyData['name'] ?? '' }}</div>
                        <div class="text-gray-700">{{ $companyData['codice_fiscale'] ?? '' }}</div>
                        <div class="text-gray-700">{{ $companyData['indirizzo'] ?? '' }}</div>
                    </div>

                    <button wire:click="confirmCompany"
                            class="w-full bg-[#b499ff] text-white font-medium rounded-xl py-4 text-lg hover:bg-[#a688f2] transition">
                        Conferma →
                    </button>
                </div>
                @elseif ($step === 4)
                    <div class="mb-6 relative">
                        <input type="text"
                            wire:model.live.debounce.500ms="residence_city"
                            placeholder="Il tuo indirizzo di residenza"
                            autocomplete="off"
                            class="w-full rounded-xl border-gray-300 px-4 py-3 placeholder-gray-400">

                        @error('residence_city')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror

                        @if (!empty($residenceCitySuggestions))
                            <ul class="absolute bg-white border rounded shadow w-full mt-1 z-10 max-h-60 overflow-y-auto">
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

                        <input type="text" maxlength="16" wire:model.defer="cf" oninput="this.value = this.value.toUpperCase()"
                                placeholder="Inserisci il tuo CF"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-center placeholder-gray-400">

                                @error('cf')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror


                            <button type="button"
                                    wire:click="completeMissingInfo"
                                    wire:loading.attr="disabled"
                                    class="mt-6 w-full bg-black text-white rounded-xl py-3 text-lg hover:bg-black/80 transition">
                                Continua
                            </button>

                    </div>
                @elseif ($step === 5)


                                <div class="mt-4 text-sm text-gray-600 space-y-1">
                                    <div>🎂 Data di nascita: <strong>{{ $birth_date }}</strong></div>
                                    <div>👤 Sesso: <strong>{{ $gender === 'M' ? 'Maschio' : 'Femmina' }}</strong></div>
                                    <div>📍 Codice Belfiore: <strong>{{ $birth_place_code }}</strong></div>
                                </div>


                                <div class="text-center space-y-6">
    <h1 class="text-2xl font-bold">Ci serve un documento valido</h1>
    <p class="text-gray-500">Carica facilmente il tuo documento di identità inquadrando il QR Code qui sotto</p>

    {{-- QR Code --}}
    <div class="flex justify-center my-4">
        <img src="{{ route('qr.show', ['uuid' => $uuid]) }}" alt="QR Code" class="w-40 h-40">
    </div>

    <div class="relative flex justify-center items-center my-4">
        <hr class="w-1/4 border-gray-300">
        <span class="mx-4 text-gray-400 font-medium">oppure</span>
        <hr class="w-1/4 border-gray-300">
    </div>

    {{-- Upload --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="border border-dashed p-4 rounded-lg text-center">
            <label class="cursor-pointer">
                @if ($document_front)
                    <div class="text-green-600">✔ {{ $document_front->getClientOriginalName() }}</div>
                @else
                    <input type="file" wire:model="document_front" class="hidden">
                    <span class="text-gray-500">Fronte</span>
                @endif
            </label>
        </div>
        <div class="border border-dashed p-4 rounded-lg text-center">
            <label class="cursor-pointer">
                @if ($document_back)
                    <div class="text-green-600">✔ {{ $document_back->getClientOriginalName() }}</div>
                @else
                    <input type="file" wire:model="document_back" class="hidden">
                    <span class="text-gray-500">Retro</span>
                @endif
            </label>
        </div>
    </div>

    <button wire:click="submitDocuments"
            wire:loading.attr="disabled"
            class="mt-6 w-full bg-[#b499ff] text-white py-3 rounded-xl text-lg hover:bg-[#a688f2] transition">
        Procedi →
    </button>
</div>


@elseif ($step === 6)


    <div class="w-full h-[600px]">
        <iframe src="{{ Storage::disk('s3')->url($pdfPath) }}" class="w-full h-full rounded shadow" />
    </div>

    <h3 class="mt-6 font-bold">Firma autografa</h3>

    <canvas id="signature-canvas" class="border rounded w-full h-48 bg-white"></canvas>

    <div class="mt-4 flex gap-4">
        <button wire:click="clearSignature" type="button">Cancella</button>
        <button wire:click="saveSignature" type="button">Firma il documento</button>
    </div>

    <input type="hidden" id="signature-data" wire:model.defer="signatureData" />

    <script>
        let canvas = document.getElementById('signature-canvas');
        let signaturePad = new SignaturePad(canvas);

        window.addEventListener('clear-signature', () => {
            signaturePad.clear();
        });

        window.addEventListener('submit-signature', () => {
            if (!signaturePad.isEmpty()) {
                const dataURL = signaturePad.toDataURL();
                @this.set('signatureData', dataURL);
            } else {
                alert('Firma non presente!');
            }
        });
    </script>

@elseif ($step === 7)

@if ($errors->has('firma'))
    <div class="text-red-500 mb-4">{{ $errors->first('firma') }}</div>
@endif

@if ($firmaUrl)
    <div class="w-full h-[80vh]">
        <iframe src="{{ $firmaUrl }}"
                class="w-full h-full border rounded"
                frameborder="0"
                allowfullscreen></iframe>
    </div>
@else
    <div class="text-center">
        <p class="mb-4">Caricamento dell'interfaccia per la firma digitale in corso...</p>
    </div>
@endif

@endif

{{-- 🧑 UTENTE (non admin) → invia dati mouse --}}
@unless(Auth::check() && Auth::user()->admin)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function throttle(fn, limit) {
            let waiting = false;
            return function (...args) {
                if (!waiting) {
                    fn.apply(this, args);
                    waiting = true;
                    setTimeout(() => { waiting = false; }, limit);
                }
            };
        }

        // Mouse move
        // Mouse move
        document.addEventListener('mousemove', throttle(e => {
            Livewire.dispatch('mouseMoved',  [e.clientX / innerWidth, e.clientY]);
        }, 100));

        // Click
        document.addEventListener('click', e => {
            Livewire.dispatch('mouseClicked', [e.clientX / innerWidth, e.clientY]);
        });

        // Focus
        document.querySelectorAll('input,select,textarea').forEach(el => {
            el.addEventListener('focus', () => {
                Livewire.dispatch('focusChanged', [el.name]);
            });
        });

    });
    </script>
    @endunless

@if(Auth::check() && Auth::user()->admin)
<div id="ghost-cursor"></div>
<div id="ghost-click" style="display:none"></div>
@endif


{{-- 👨‍💼 ADMIN → vede il cursore fantasma aggiornato in real time --}}
@push('scripts')
@if(Auth::check() && Auth::user()->admin)
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cursor = document.getElementById('ghost-cursor');
    const click  = document.getElementById('ghost-click');

    Echo.channel('ghost-mouse.{{ $uuid }}')
        .listen('.mouse.move',  e => {
            cursor.style.left = `${e.x * innerWidth}px`;
            cursor.style.top  = `${e.y}px`;
        })
        .listen('.mouse.click', e => {
            click.style.left = `${e.x * innerWidth}px`;
            click.style.top  = `${e.y}px`;
            click.style.display = 'block';
            click.style.animation = 'click-anim .4s ease-out';
            setTimeout(() => click.style.display = 'none', 400);
        })
        .listen('.focus.change', e => {
            document.querySelectorAll('.ghost-focused').forEach(el => el.classList.remove('ghost-focused'));
            document.querySelector(`[name="${e.name}"]`)?.classList.add('ghost-focused');
        });
});
</script>
@endif
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    Echo.channel('wizard-step.{{ $uuid }}')
        .listen('.step.updated', e => {
            console.log('[Broadcast] Step ricevuto:', e.step); // ✅ debug
            Livewire.dispatch('syncStepFromBroadcast', { step: e.step });
        });
});
</script>
@endpush

</div>
