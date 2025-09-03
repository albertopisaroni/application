<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\FiscoApiService;
use Illuminate\Support\Facades\Artisan;

class FiscoapiSessionButton extends Component
{
    public $showModal = false;
    public $loading = false;
    public $session = null;
    public $error = null;
    public $swal = null;

    public function openModal()
    {
        $this->showModal = true;
        $this->error = null;
        $this->session = null;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->loading = false;
        $this->session = null;
        $this->error = null;
    }

    public function avviaSessione(FiscoApiService $fiscoApi)
    {
        $this->loading = true;
        $this->error = null;
        $this->session = null;
        $this->dispatch('enable-fiscoapi-polling');
        try {
            $session = $fiscoApi->avviaSessione('agenzia_entrate', 'poste');
            if ($session) {
                $this->session = $session->toArray();
                $this->loading = false;
                $this->pollSession();
            } else {
                $this->error = 'Errore avvio sessione';
                $this->loading = false;
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->loading = false;
        }
    }

    public function pollSession()
    {
        if (!$this->session || in_array($this->session['stato'], ['sessione_attiva', 'errore'])) {
            return;
        }
        $this->dispatch('poll-fiscoapi-session', ['id_sessione' => $this->session['id_sessione']]);
    }

    public function fetchSession($id_sessione)
    {
        if (!$id_sessione) return;
        $session = \App\Models\FiscoapiSession::where('id_sessione', $id_sessione)->first();
        if ($session) {
            $this->session = $session->toArray();

            // Stato finale: ferma polling, chiudi popup, mostra SweetAlert
            if (in_array($session->stato, ['sessione_attiva', 'autenticato', 'errore', 'qr_code_scaduto'])) {
                $this->showModal = false;
                $this->loading = false;

                if ($session->stato === 'sessione_attiva' || $session->stato === 'autenticato') {
                    
                    // Usa il job invece di Artisan::queue per evitare duplicati
                    \App\Jobs\FiscoapiPostLoginJob::dispatch($session->id_sessione);

                    $this->swal = [
                        'title' => 'Sessione attiva!',
                        'text' => 'Hai effettuato l’accesso con successo.',
                        'icon' => 'success'
                    ];
                } elseif ($session->stato === 'qr_code_scaduto') {
                    $this->swal = [
                        'title' => 'QR code scaduto',
                        'text' => 'Il QR code è scaduto, riprova.',
                        'icon' => 'warning'
                    ];
                } else {
                    $this->swal = [
                        'title' => 'Errore',
                        'text' => 'Si è verificato un errore nella sessione.',
                        'icon' => 'error'
                    ];
                }
                $this->dispatch('show-swal', $this->swal);
                return;
            }

            // Continua il polling solo se non è uno stato finale
            $this->pollSession();
        }
    }

    public function render()
    {
        return view('livewire.fiscoapi-session-button');
    }
}
