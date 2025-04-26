<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Registration;
use Illuminate\Support\Facades\Mail;
use App\Mail\OnboardingLinkMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class RegistrationDetailTable extends Component
{
    public Registration $registration;
    public bool $showOnboardingOptions = false;
    public ?int $lastSmsSentAt = null;
    public array $userFields = [];
    public array $marketingFields = [];

    
    protected $listeners = ['registrationUpdated' => '$refresh'];

    public function toggleOnboardingOptions()
    {
        if (empty($this->registration->name) || empty($this->registration->surname)) {
            session()->flash('message', 'Per iniziare l\'onboarding, inserisci prima nome e cognome.');
            return;
        }
    
        $this->showOnboardingOptions = !$this->showOnboardingOptions;
    }

    public function sendOnboardingEmail()
    {
        $email = $this->registration->email;
    
        if (!$email) {
            session()->flash('message', 'Nessuna email disponibile.');
            return;
        }
    
        $link = route('guest.onboarding', ['uuid' => $this->registration->uuid]);
    
        Mail::to($email)->send(new OnboardingLinkMail($link));
    
        session()->flash('message', 'Link onboarding inviato via email!');
    }

    public function sendOnboardingWhatsapp()
    {
        $phone = $this->registration->phone;

        if (!$phone) {
            session()->flash('message', 'Nessun numero di telefono disponibile.');
            return;
        }

        $normalized = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($normalized) === 10) {
            $normalized = '+39' . $normalized;
        } elseif (strpos($normalized, '39') === 0 && strlen($normalized) === 12) {
            $normalized = '+39' . substr($normalized, 2);
        } elseif (strpos($normalized, '+39') !== 0) {
            $normalized = '+39' . ltrim($normalized, '0');
        }

        $link = route('guest.onboarding', ['uuid' => $this->registration->uuid]);

        try {
            $twilio = new \Twilio\Rest\Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $twilio->messages->create(
                'whatsapp:' . $normalized,
                [
                    'from' => config('services.twilio.whatsapp_from'),
                    'body' => "Ciao! Completa la registrazione: $link"
                ]
            );

            session()->flash('message', 'Link onboarding inviato via WhatsApp!');
        } catch (\Exception $e) {
            logger()->error('Errore invio WhatsApp', ['error' => $e->getMessage()]);
            session()->flash('message', 'Errore durante l’invio via WhatsApp.');
        }
    }

    public function sendOnboardingSms()
    {
        $now = now()->timestamp;

        // Se già inviato e non sono passati 90 secondi, blocca
        if ($this->lastSmsSentAt && ($now - $this->lastSmsSentAt) < 90) {
            session()->flash('message', 'Attendi prima di inviare un altro SMS.');
            return;
        }

        $phone = $this->registration->phone;
        if (!$phone) {
            session()->flash('message', 'Nessun numero di telefono disponibile.');
            return;
        }

        // Normalizza il numero
        $normalized = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($normalized) === 10) {
            $normalized = '+39-' . $normalized;
        } elseif (strpos($normalized, '39') === 0 && strlen($normalized) === 12) {
            $normalized = '+39-' . substr($normalized, 2);
        } elseif (strpos($normalized, '+39') === 0 && strlen($normalized) === 13) {
            $normalized = '+39-' . substr($normalized, 3);
        } else {
            $normalized = '+39-' . ltrim($normalized, '0');
        }

        $link = route('guest.onboarding', ['uuid' => $this->registration->uuid]);

        $payload = [
            'test' => false,
            'sender' => 'Newo',
            'body' => "Ciao! Completa la registrazione: $link",
            'recipients' => $normalized,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openapi.sms.token'),
            'Content-Type' => 'application/json',
        ])->post(config('services.openapi.sms.url') . '/messages/', $payload);

        if ($response->successful()) {
            $this->lastSmsSentAt = $now;
            session()->flash('message', 'Link onboarding inviato via SMS!');
        } else {
            session()->flash('message', 'Errore durante l’invio SMS.');
            logger()->error('Errore invio SMS', [
                'payload' => $payload,
                'response' => $response->body(),
                'status' => $response->status(),
            ]);
        }
    }

    public function getUserFieldsProperty()
    {
        return collect($this->fields)->except($this->marketingFields())->all();
    }

    public function getMarketingFieldsProperty()
    {
        return collect($this->fields)->only($this->marketingFields())->all();
    }

    public function mount(Registration $registration)
    {
        $this->registration = $registration;
        $allFields = collect($registration->getAttributes());

        $marketingKeys = [
            'location', 'label', 'utm_source', 'utm_medium', 'utm_campaign',
            'utm_content', 'ab_variant', 'page_time', 'scroll_time', 'scroll_bounce',
            'mouse_movement', 'form_time_fullname', 'form_time_email', 'form_time_phone',
            'form_autofill_fullname', 'form_autofill_email', 'form_autofill_phone',
            'section_time_fatture_e_pagamenti', 'section_time_flussi_di_lavoro',
            'section_time_tasse_e_scadenze', 'section_time_il_ai_automazioni_intelligenti',
            'section_time_il_nostro_team_e_qui_per_te', 'section_time_con_noi_essere_freelance',
            'section_time_newo_e_pensato_per_farti_crescere', 'section_time_newo_e_gia_la_scelta',
            'behavior_profile', 'behavior_score',
        ];

        $this->marketingFields = $allFields->only($marketingKeys)->toArray();
        $this->userFields = $allFields->except($marketingKeys)->toArray();
    }

    private function marketingFields()
    {
        return [
            'location',
            'label',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'ab_variant',
            'page_time',
            'scroll_time',
            'scroll_bounce',
            'mouse_movement',
            'form_time_fullname',
            'form_time_email',
            'form_time_phone',
            'form_autofill_fullname',
            'form_autofill_email',
            'form_autofill_phone',
            'section_time_fatture_e_pagamenti',
            'section_time_flussi_di_lavoro',
            'section_time_tasse_e_scadenze',
            'section_time_il_ai_automazioni_intelligenti',
            'section_time_il_nostro_team_e_qui_per_te',
            'section_time_con_noi_essere_freelance',
            'section_time_newo_e_pensato_per_farti_crescere',
            'section_time_newo_e_gia_la_scelta',
            'behavior_profile',
            'behavior_score',
        ];
    }

    public function getFieldsProperty()
    {
        return collect($this->registration->fresh()->getAttributes())
            ->except(['uuid', 'user_id']);
    }

    public function getCurrentStepProperty()
    {
        return $this->registration->fresh()->step ?? 1;
    }

    public function getPercentageProperty()
    {
        return ($this->currentStep / 7) * 100;
    }

    public function render()
    {
        return view('livewire.admin.registration-detail-table');
    }
}