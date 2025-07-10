<div x-data @poll-fiscoapi-session.window="if (window.fiscoapiPollingActive) $wire.fetchSession($event.detail[0].id_sessione)">
    <button wire:click="openModal" class="btn btn-primary">
        Avvia sessione FiscoApi
    </button>

    @if($showModal)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
                <button class="absolute top-2 right-2 text-gray-500" wire:click="closeModal">&times;</button>
                <h2 class="text-lg font-bold mb-4">Sessione FiscoApi</h2>

                @if($error)
                    <div class="text-red-600 mb-2">{{ $error }}</div>
                @endif

                @if($loading)
                    <div class="text-center py-8">
                        <span class="loader"></span>
                        <div>Avvio sessione...</div>
                    </div>
                @elseif($session)
                    <div class="mb-2">
                        <strong>Stato:</strong> {{ $session['stato'] ?? '-' }}
                    </div>
                    @if(!empty($session['qr_code']))
                        <div class="mb-4 text-center">
                            <img src="{{ $session['qr_code'] }}" alt="QR Code" class="mx-auto max-w-xs" />
                            <div class="text-xs text-gray-500 mt-2">Scansiona con l'app PosteID</div>
                        </div>
                    @endif
                    @if($session['stato'] === 'sessione_attiva' || $session['stato'] === 'autenticato')
                        <div class="text-green-600 font-bold">Sessione attiva!</div>
                    @elseif($session['stato'] === 'errore')
                        <div class="text-red-600 font-bold">Errore nella sessione.</div>
                    @else
                        <div class="text-gray-500 text-sm">In attesa di completamento...</div>
                    @endif
                @else
                    <button wire:click="avviaSessione" class="btn btn-success w-full">Avvia sessione</button>
                @endif
            </div>
        </div>
    @endif

    <script>
        window.fiscoapiPollingActive = true;

        document.addEventListener('show-swal', function(e) {
            window.fiscoapiPollingActive = false; // blocca il polling
            Swal.fire({
                title: e.detail[0].title,
                text: e.detail[0].text,
                icon: e.detail[0].icon,
                confirmButtonText: 'Chiudi'
            });
        });
        document.addEventListener('enable-fiscoapi-polling', function() {
            window.fiscoapiPollingActive = true;
        });
        document.addEventListener('poll-fiscoapi-session', function(e) {
            if (!window.fiscoapiPollingActive) return;
            setTimeout(function() {
                const id_sessione = e.detail[0].id_sessione;
                window.dispatchEvent(new CustomEvent('poll-fiscoapi-session', { detail: [{ id_sessione }] }));
            }, 1000);
        });
    </script>

    <style>
    .loader {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    animation: spin 1s linear infinite;
    display: inline-block;
    }
    @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
    }
    </style>


</div>