<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;

use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use Barryvdh\DomPDF\Facade\Pdf;

use BaconQrCode\Renderer\Image\PngRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

use App\Models\Registration;
use App\Models\User;
use App\Models\Company;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\Channel;


class StepUpdated implements ShouldBroadcastNow
{
    public function __construct(public string $uuid, public int $step) {}

    public function broadcastOn()
    {
        return new Channel('wizard-step.' . $this->uuid);
    }

    public function broadcastAs()
    {
        return 'step.updated';
    }
}

class OnboardingWizard extends Component
{

    use WithFileUploads;

    public ?Registration $registration = null;
    public int $step = 1;

    public $document_front;
    public $document_back;

    public $firmaUrl = null;

    public string $email = '';
    public string $name = '';
    public string $surname = '';
    public ?string $uuid = null;
    public bool $existingUser = false;

    public bool $needsPasswordCheck = false;
    public string $password = '';
    public string $searchQuery = '';
    public array $companyData = [];
    public array $stepHistory = [];

    public string $cf = '';
    public string $residenza = '';

    public string $residence_city = '';
    public array $residenceCitySuggestions = [];

    public ?string $residence_city_display = null;
    public ?string $residence_address = null;
    public ?string $residence_province = null;
    public ?string $residence_country = 'IT';
    public ?string $residence_cap = null;

    public ?string $birth_date = null;
    public ?string $birth_place_code = null;
    public ?string $gender = null;

    public string $delegante_nome = '';
    public string $delegante_cf = '';
    public string $delegante_piva = '';

    public string $telefono = '';
    public array $prefissi = ['+39', '+33', '+34', '+49', '+41', '+44', '+1'];
    public string $prefisso = '+39';

    public $ghostX = 0;
    public $ghostY = 0;

    // Livewire component
    protected $listeners = [
        'mouseMoved'   => 'onMouseMoved',
        'mouseClicked' => 'onMouseClicked',
        'focusChanged' => 'onFocusChanged',
        'syncStepFromBroadcast' => 'updateStepExternally',
    ];

    public function updateStepExternally($step)
    {
            $this->step = $step;

            logger('Step aggiornato esternamente:', [
                'step' => $this->step,
                'uuid' => $this->uuid,
            ]);

            $this->dispatch('$refresh');

    }

    // OnboardingWizard.php
    public function onMouseMoved($x, $y)
    {
        if (auth()->user()?->admin) return;
        // broadcast(new \App\Events\MouseMoved($this->uuid, $x, $y));
    }

    public function onMouseClicked($x, $y)
    {
        if (auth()->user()?->admin) return;
        // broadcast(new \App\Events\MouseClicked($this->uuid, $x, $y));
    }

    public function onFocusChanged($name)
    {
        if (auth()->user()?->admin) return;
        // broadcast(new \App\Events\FocusChanged($this->uuid, $name));
    }



    public function goToStep(int $step, bool $saveToHistory = true)
    {
        if ($saveToHistory) {
            $this->stepHistory[] = $this->step;
        }

        $this->step = $step;


        $this->registration->update([
            'step' => $step,
            'step_history' => json_encode($this->stepHistory),
        ]);

        // broadcast(new StepUpdated($this->uuid, $this->step))->toOthers();

    }

    public function goBack()
    {
        if (!empty($this->stepHistory)) {
            $this->step = array_pop($this->stepHistory);

    
            $this->registration->update([
                'step' => $this->step,
                'step_history' => json_encode($this->stepHistory),
            ]);

            // broadcast(new StepUpdated($this->uuid, $this->step))->toOthers();
        
            
        }
    }

    public function qr($uuid)
    {
        $url = route('registration.upload.mobile', ['uuid' => $uuid]);

        $renderer = new PngRenderer(
            new RendererStyle(300),
            new \BaconQrCode\Renderer\Image\ImagickImageBackEnd() // o GdImageBackEnd
        );

        $writer = new Writer($renderer);
        $qrCodeImage = $writer->writeString($url);

        return Response::make($qrCodeImage, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr.png"',
        ]);
    }

    public function generaDelegaECarica()
    {
        $data = [
            'delegante_nome' => $this->delegante_nome,
            'delegante_cf' => $this->delegante_cf,
            'delegante_piva' => $this->delegante_piva,
            'delegato_nome' => 'Alberto Pisaroni',
            'delegato_cf' => 'PSRLRT95R24G337P',
            'delegato_societa' => 'HOLDINGS SHAKE DI PISARONI ALBERTO',
            'delegato_piva' => '02939450348',
            'data_delega' => now()->format('d/m/Y'),
        ];

        // Genera il PDF
        $pdf = Pdf::loadView('pdf.delega_ae', $data)->output();

        // Salva su S3 come "DELEGA.pdf"
        Storage::disk('s3')->put("newo/registrazioni/{$this->uuid}/DELEGA.pdf", $pdf);

        // (Opzionale) salva il path su DB o segnala successo
        session()->flash('message', 'PDF caricato su S3 con successo!');
    }

    public function mount(?string $uuid = null)
    {
        if ($uuid) {
            $this->registration = Registration::where('uuid', $uuid)->firstOrFail();
            $this->uuid = $uuid;

            session(['uuid' => $uuid]);

            $this->email = $this->registration->email ?? '';
            $this->name = $this->registration->name ?? '';
            $this->surname = $this->registration->surname ?? '';
            $this->companyData['piva'] = $this->piva = $this->registration->piva ?? '';
            $this->cf = $this->registration->cf ?? '';
            $this->residence_city = $this->registration->residenza ?? '';
            $this->residence_address = $this->registration->indirizzo ?? '';
            $this->residence_province = $this->registration->provincia ?? '';
            $this->residence_cap = $this->registration->cap ?? '';
            $this->birth_date = $this->registration->birth_date ?? null;
            $this->birth_place_code = $this->registration->birth_place_code ?? null;
            $this->companyData['name'] = $this->company_name = $this->registration->company_name ?? '';
            $this->companyData['indirizzo'] = $this->company_address = $this->registration->company_address ?? '';
            $this->companyData['codice_fiscale'] = $this->company_cf = $this->registration->company_cf ?? '';


            $this->stepHistory = json_decode($this->registration->step_history ?? '[]', true);
            $this->goToStep($this->registration->step ?? 2, false);

        }
    }

    public function updatedCf($value)
    {
        $value = strtoupper(trim($value));

        try {
            $cf = new CodiceFiscale();
            $cf->parse($value);

            $this->birth_date = $cf->getBirthDate()?->format('Y-m-d');
            $this->gender = $cf->getGender();
            $this->birth_place_code = $cf->getBirthPlaceComplete(); // questo è il metodo corretto

            // Verifica se coincide con i dati inseriti
            $expected = CodiceFiscale::generate(
                $this->name,
                $this->surname,
                $cf->getBirthDate(),
                $cf->getBirthPlaceComplete(),
                $cf->getGender()
            );

            if (strtoupper($value) !== $expected) {
                $this->addError('cf', 'Il CF non corrisponde ai dati inseriti.');
            }
        } catch (\Exception $e) {
            $this->addError('cf', 'Codice Fiscale non valido: ' . $e->getMessage());
        }
    }


    public function updatedResidenceCity($value)
    {
        $this->residence_province = null;
        $this->residence_country = 'IT';
        $this->residenceCitySuggestions = [];

        if (strlen($value) < 3) {
            return;
        }

        $apiKey = config('services.google.places_key');

        $response = Http::get("https://maps.googleapis.com/maps/api/place/autocomplete/json", [
            'input' => $value,
            'language' => 'it_IT',
            'types' => 'geocode',
            'components' => 'country:it',
            'key' => $apiKey,
        ]);

        $this->residenceCitySuggestions = $response->json('predictions') ?? [];
    }

    public function selectResidenceCity($description, $placeId, $termsJson = null)
    {
        $this->residence_city = $description;
        $this->residenceCitySuggestions = [];

        // Controlla se $termsJson è già un array oppure è una stringa JSON
        if (is_string($termsJson)) {
            $terms = json_decode($termsJson, true);
        } else {
            $terms = $termsJson;
        }
        if (!is_array($terms)) {
            $this->addError('residence_city', 'Dati dei termini non validi.');
            return;
        }

        // Mappa i termini in base al numero di elementi.
        if (count($terms) === 5) {
            // Struttura: [0] Via, [1] Numero, [2] Comune, [3] Provincia, [4] Paese
            $this->residence_address = $terms[0]['value'] ?? null;
            $this->residence_city_display = $terms[2]['value'] ?? $description;
            $this->residence_province = $terms[3]['value'] ?? null;
            $this->residence_country = $terms[4]['value'] ?? 'Italia';
        } elseif (count($terms) === 4) {
            // Struttura: [0] Via, [1] Comune, [2] Provincia, [3] Paese
            $this->residence_address = $terms[0]['value'] ?? null;
            $this->residence_city_display = $terms[1]['value'] ?? $description;
            $this->residence_province = $terms[2]['value'] ?? null;
            $this->residence_country = $terms[3]['value'] ?? 'Italia';
        } else {
            // Fallback generico se la struttura non è standard
            $this->residence_address = $terms[0]['value'] ?? null;
            $this->residence_city_display = $terms[2]['value'] ?? $description;
            $this->residence_province = $terms[3]['value'] ?? null;
            $this->residence_country = $terms[4]['value'] ?? 'Italia';
        }

        // Chiamata per ottenere il CAP (postal_code) tramite Place Details
        $apiKey = config('services.google.places_key');
        $detailsResponse = Http::get("https://maps.googleapis.com/maps/api/place/details/json", [
            'place_id' => $placeId,
            'fields'   => 'address_component',
            'language' => 'it',
            'key'      => $apiKey,
        ]);
        $components = $detailsResponse->json('result.address_components');

        $this->residence_cap = collect($components)
            ->filter(function ($component) {
                return in_array('postal_code', $component['types']);
            })
            ->pluck('long_name')
            ->first() ?? null;

        logger('Residence terms processed', $terms);
        logger('Residence selected:', [
            'description'   => $description,
            'placeId'       => $placeId,
            'address'       => $this->residence_address,
            'city'          => $this->residence_city_display,
            'province'      => $this->residence_province,
            'country'       => $this->residence_country,
            'cap'           => $this->residence_cap,
        ]);
    }


    public function searchCompany()
    {
        $this->validate([
            'searchQuery' => 'required|string|min:8',
        ]);

        $piva = $this->searchQuery;
        if (str_starts_with($piva, 'IT')) {
            $piva = substr($piva, 2);
        }

        // 🚫 Check se la P.IVA esiste già
        if (Company::where('piva', $piva)->exists()) {
            $this->addError('searchQuery', 'Questa società è già registrata su Newo.');
            return;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openapi.company.token'),
        ])->get(env("OPENAPI_COMPANY_URL") . '/IT-start/' . $piva);

        if (! $response->successful() || empty($response->json()['data'][0])) {
            $this->addError('searchQuery', 'Impossibile recuperare i dati aziendali.');
            return;
        }

        $data = $response->json()['data'][0];
        $address = $data['address']['registeredOffice'] ?? [];

        $this->companyData = [
            'name' => $data['companyName'] ?? '',
            'piva' => $piva,
            'codice_fiscale' => $data['taxCode'] ?? '',
            'indirizzo' => trim(($address['streetName'] ?? '') . ', ' . ($address['streetNumber'] ?? '') . ', ' . ($address['zipCode'] ?? '') . ', ' . ($address['town'] ?? '')),
        ];

        // Step 5: conferma visuale
        $this->gotostep(3);
    }

    public function confirmCompany()
    {
        $this->registration->update([
            'piva'            => $this->companyData['piva'],
            'company_name'    => $this->companyData['name'],
            'company_address' => $this->companyData['indirizzo'],
            'company_cf'      => $this->companyData['codice_fiscale'],
        ]);

        $this->goToStep($this->existingUser ? 5 : 4);
    }

    public function verifyPassword()
    {
        $this->validate([
            'password' => 'required|string',
        ]);

        $user = User::where('email', $this->email)->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            $this->addError('password', 'Password non corretta.');
            return;
        }

        // Autenticato con la sua password → continua onboarding
        $this->registration = Registration::firstOrCreate(
            ['email' => $this->email],
            ['uuid' => Str::uuid()]
        );

        $this->uuid = $this->registration->uuid;
        $this->name = $user->name ?? '';
        $this->surname = $user->surname ?? '';
        $this->goToStep(2);
    }

    public function completeMissingInfo()
    {
        $this->validate([
            'cf' => 'required|string|min:16|max:16',
            'residence_city' => 'required|string',
            'residence_city_display' => 'nullable|string',
            'residence_address' => 'nullable|string',
            'residence_province' => 'nullable|string',
            'residence_cap' => 'nullable|string',
        ]);

        if (! $this->birth_date && $this->cf) {
            $this->updatedCf($this->cf);
        }

        $this->registration->update([
            'cf' => $this->cf,
            'residenza' => $this->residence_city_display ?? $this->residence_city,
            'indirizzo' => $this->residence_address,
            'provincia' => $this->residence_province,
            'cap' => $this->residence_cap,
            'birth_date' => $this->birth_date,
            'birth_place_code' => $this->birth_place_code,
            'gender' => $this->gender,
        ]);

        $this->gotostep(5);
    }

    public function submitName()
    {
        $this->validate([
            'name' => 'required',
            'surname' => 'required',
        ]);

        $this->registration->update([
            'name' => $this->name,
            'surname' => $this->surname,
        ]);

        $this->goToStep(2);
    }

    public function submitDocuments()
    {
        $this->validate([
            'document_front' => 'required|file|mimes:jpeg,png,pdf|max:5120',
            'document_back' => 'required|file|mimes:jpeg,png,pdf|max:5120',
        ]);

        $folder = 'newo/registrazioni/' . $this->uuid;

        $extFront = $this->document_front->getClientOriginalExtension();
        $extBack = $this->document_back->getClientOriginalExtension();

        $frontPath = $this->document_front->storeAs($folder, "fronte.$extFront", 's3');
        $backPath = $this->document_back->storeAs($folder, "retro.$extBack", 's3');

        $this->registration->update([
            'document_front' => $frontPath,
            'document_back' => $backPath,
        ]);

        $this->delegante_nome = $this->name . ' ' . $this->surname;
        $this->delegante_cf = $this->cf;
        $this->delegante_piva = $this->registration->piva;

        // 1. Genera PDF
        $data = [
            'delegante_nome' => $this->delegante_nome,
            'delegante_cf' => $this->delegante_cf,
            'delegante_piva' => $this->delegante_piva,
            'delegato_nome' => 'Alberto Pisaroni',
            'delegato_cf' => 'PSRLRT90A01G337Y',
            'delegato_societa' => 'NEWO S.R.L.',
            'delegato_piva' => '12345678901',
            'data_delega' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('pdf.delega_ae', $data)->output();

        $pdfPath = "$folder/DELEGA.pdf";
        Storage::disk('s3')->put($pdfPath, $pdf);

        // 👉 A questo punto NON avvii la firma, ma vai allo step 8
        $this->gotostep(7);
    }

    public function goToExistingPiva()
    {
        $this->gotostep(2);

    }

    public function render()
    {
        logger('[Livewire] Render chiamato – step attuale: ' . $this->step);
        return view('livewire.onboarding-wizard');
    }
}
