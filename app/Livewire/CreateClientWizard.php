<?php

namespace App\Livewire;

use Livewire\Component;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

use App\Models\AtecoCode;

class CreateClientWizard extends Component
{
    public $step = 1;

    public $first_name;
    public $last_name;
    public $business_description;

    public $birth_date;


    public $birth_address;
    public $birth_city;
    public $birth_province;
    public $birth_country = 'IT';
    public $birth_cap;
    public $birthCitySuggestions = [];

    public $residence_address;
    public $residence_city;
    public $residence_province;
    public $residence_country = 'IT';
    public $residence_cap;
    public $residenceCitySuggestions = [];

    public $personal_email; 
    public $personal_phone;

    public $ateco_suggestions = [];        // Suggerimenti provenienti da ChatGPT
    public $selected_ateco_codes = [];       // I codici scelti (lista di stringhe, es. ['62.01.00', '62.02.00'])
    public $ateco_manual_suggestions = [];   // Suggerimenti per l'autocomplete dal DB
    public $ateco_query;           
    
    public $ateco_list = [];// La query digitata per cercare manualmente nel DB

    
    public function render()
    {
        return view('livewire.create-company-wizard');
    }

    public function mount()
{
    $this->ateco_list = AtecoCode::all()->toArray();
}

    public function updatedAtecoQuery($value)
    {
        if(strlen($value) < 2) {
            $this->ateco_manual_suggestions = [];
            return;
        }
        
        // Cerca per codice o descrizione, limitando a 10 risultati
        $this->ateco_manual_suggestions = AtecoCode::where('code', 'like', "%{$value}%")
            ->orWhere('description', 'like', "%{$value}%")
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function loadAtecoFromChatGPT()
    {
        if (!$this->business_description) {
            return;
        }
        
        $prompt = "In base alla seguente descrizione dell'attività, genera un JSON contenente un array di oggetti con al massimo 3 suggerimenti, non per forza ne devi indicare sempre 3, minimo 0, massimo 3. Ogni oggetto deve avere le chiavi 'code' e 'description'. La descrizione dell'attività è: \"{$this->business_description}\".";
        
        $apiKey = config('services.openai.api_key');
        
        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'Sei un esperto in classificazione ATECO per il mercato italiano (SOLO CODICI ATECO ISTAT 2025). Rispondi con codici ateco solo se pertinenti, in caso di una descrizione attivita non chiara non rispondere con nessun codice ateco. Rispondi solo con il JSON richiesto.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.5,
                'max_tokens' => 250, // Limite maggiore per garantire una risposta completa
            ]);
        
        // Log per debug: visualizza la risposta completa di OpenAI
        logger('OpenAI response', $response->json());
        
        // Estrai e pulisci il contenuto della risposta
        $content = trim($response->json('choices.0.message.content'));
        $data = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            // Se la risposta contiene la chiave "suggestions", usala
            if (isset($data['suggestions'])) {
                $this->ateco_suggestions = $data['suggestions'];
            } else {
                $this->ateco_suggestions = $data;
            }
            // Unisci automaticamente i suggerimenti ai codici selezionati, se non già presenti
            foreach ($this->ateco_suggestions as $sugg) {
                if (isset($sugg['code']) && !in_array($sugg['code'], $this->selected_ateco_codes)) {
                    $this->selected_ateco_codes[] = $sugg['code'];
                }
            }
        } else {
            logger('Errore nel parsing del JSON da OpenAI', ['content' => $content]);
            $this->ateco_suggestions = [];
        }
    }

    public function nextStep()
    {
        if ($this->step === 1) {
            $this->validate([
                'first_name' => 'required|string|min:2',
                'last_name' => 'required|string|min:2',
            ]);
        }

        elseif ($this->step === 2) {
            $this->validate([
                'business_description' => 'required|string|min:10',
            ]);
        }

        elseif ($this->step === 3) {

            $this->validate([
                'birth_date' => 'required|date',
                'birth_city' => 'required|string',
            ]);
        
            if (!$this->birth_province) {
                $this->addError('birth_city', 'Seleziona un comune valido dall’elenco.');
                return;
            }

        } elseif ($this->step === 4) {
            $this->validate([
                'residence_address' => 'required|string',
                'residence_city'    => 'required|string',
            ]);
            if (!$this->residence_province) {
                $this->addError('residence_city', 'Seleziona un comune valido dall’elenco per la residenza.');
                return;
            }
        } elseif ($this->step === 5) {
            // Validazione per i contatti
            $this->validate([
                'personal_email' => 'required|email',
                'personal_phone' => 'required|string|min:5',
            ]);

            $this->loadAtecoFromChatGPT();
        }

        

        $this->step++;
    }

    

    public function previousStep()
    {
        $this->step--;
    }


    public function updatedBirthCity($value)
    {
        $this->birth_province = null;
        $this->birth_country = 'IT';
        $this->birthCitySuggestions = [];

        if (strlen($value) < 3) {
            return;
        }

        $apiKey = config('services.google.places_key');

        $response = Http::get("https://maps.googleapis.com/maps/api/place/autocomplete/json", [
            'input' => $value,
            'language' => 'it_IT',
            'types' => '(cities)',
            'components' => 'country:it',
            'key' => $apiKey,
        ]);

        $this->birthCitySuggestions = $response->json('predictions');
    }


    public function updatedResidenceCity($value)
    {
        // Reimposta le proprietà correlate
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

        $this->residenceCitySuggestions = $response->json('predictions');
    }

    public function selectBirthCity($description, $placeId, $termsJson = null)
    {
        $this->birth_city = $description;
        // Svuota le suggestions
        $this->birthCitySuggestions = [];

        // Se il parametro $termsJson è una stringa, decodificalo, altrimenti usalo direttamente.
        if (is_string($termsJson)) {
            $terms = json_decode($termsJson, true);
        } else {
            $terms = $termsJson;
        }

        // Verifica che $terms sia un array
        if (!is_array($terms)) {
            $this->addError('birth_city', 'Dati dei termini non validi.');
            return;
        }

        logger('terms', $terms);

        if (count($terms) === 3) {
            // Caso in cui Google restituisce 3 termini (tipicamente: [Città, Provincia, Paese])
            $this->birth_address      = null;  
            $this->birth_city_display = $terms[0]['value'] ?? $description;
            $this->birth_province     = $terms[1]['value'] ?? null;    
            $this->birth_country      = $terms[2]['value'] ?? 'Italia';
        } else {
            // Fallback generico
            $this->birth_address      = $terms[0]['value'] ?? null;  
            $this->birth_city_display = $terms[2]['value'] ?? $description;
            $this->birth_province     = $terms[3]['value'] ?? null;    
            $this->birth_country      = $terms[4]['value'] ?? 'Italia';
        }

        // logger('birth_province', $this->birth_province);

        // Ora, per ottenere il CAP, effettua una chiamata a Place Details:
        $apiKey = config('services.google.places_key');
        $detailsResponse = Http::get("https://maps.googleapis.com/maps/api/place/details/json", [
            'place_id' => $placeId,
            'fields'   => 'address_component',
            'language' => 'it',
            'key'      => $apiKey,
        ]);
        $components = $detailsResponse->json('result.address_components');
        
        $this->birth_cap = collect($components)
            ->filter(function ($component) {
                return in_array('postal_code', $component['types']);
            })
            ->pluck('long_name')
            ->first() ?? null;

        logger('Terms processed', $terms);
        logger('City selected:', [
            'description' => $description,
            'placeId'     => $placeId,
            'address'     => $this->birth_address,
            'city'        => $this->birth_city_display,
            'province'    => $this->birth_province,
            'country'     => $this->birth_country,
            'cap'         => $this->birth_cap,
        ]);
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


}