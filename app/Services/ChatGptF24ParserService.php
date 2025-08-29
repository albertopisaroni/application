<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatGptF24ParserService
{
    private string $apiKey;
    private string $model = 'gpt-4o';
    private string $tempDir;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->tempDir = ''; // Inizializza come stringa vuota
    }

    /**
     * Parsa il contenuto F24 usando ChatGPT con PDF allegato
     */
    public function parseF24Content(string $pdfContent, string $filename): array
    {
        // Crea directory temporanea unica per questo file
        $this->tempDir = storage_path('app/temp/f24_chatgpt_' . uniqid());
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
        
        try {
            Log::info("🤖 INIZIO ChatGptF24ParserService", [
                'filename' => $filename,
                'content_length' => strlen($pdfContent),
                'temp_dir' => $this->tempDir
            ]);

            // 1. Salva il PDF temporaneamente
            $pdfPath = $this->tempDir . '/input.pdf';
            file_put_contents($pdfPath, $pdfContent);
            
            // 2. Chiama ChatGPT con PDF allegato
            $jsonResponse = $this->callChatGptWithPdf($pdfPath);
            
            // 3. Parsa la risposta JSON
            $parsedData = $this->parseJsonResponse($jsonResponse);
            
            // 4. Converti in formato Tax
            $taxRecords = $this->convertToTaxRecords($parsedData);
            
            Log::info("✅ ChatGptF24ParserService completato", [
                'filename' => $filename,
                'tax_records_count' => count($taxRecords)
            ]);

            return $taxRecords;

        } catch (\Exception $e) {
            Log::error("❌ Errore in ChatGptF24ParserService", [
                'error' => $e->getMessage(),
                'filename' => $filename
            ]);
            return [];
        } finally {
            $this->cleanup();
        }
    }



    /**
     * Chiama l'API di ChatGPT con PDF allegato con retry automatico
     */
    private function callChatGptWithPdf(string $pdfPath): string
    {
        $maxRetries = 10;
        $attempt = 1;
        
        while ($attempt <= $maxRetries) {
            try {
                Log::info("🤖 Tentativo {$attempt}/{$maxRetries} - Chiamata API ChatGPT");
                
                // 1. Carica il file su OpenAI
                $fileId = $this->uploadFileToOpenAI($pdfPath);
                
                // 2. Chiama ChatGPT con il file_id
                $response = Http::timeout(60)->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Sei un parser fiscale esperto. Analizza il PDF F24 allegato e estrai TUTTI i dati presenti.'
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $this->buildPrompt()
                                ],
                                [
                                    'type' => 'file',
                                    'file' => [
                                        'file_id' => $fileId
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0,
                    'max_tokens' => 4000
                ]);

                if (!$response->successful()) {
                    throw new \Exception('Errore API ChatGPT: ' . $response->body());
                }

                $result = $response->json();
                $jsonResponse = $result['choices'][0]['message']['content'] ?? '';
                
                Log::info("✅ ChatGPT risposta ricevuta al tentativo {$attempt}", [
                    'response_length' => strlen($jsonResponse),
                    'response_complete' => $jsonResponse,
                    'response_preview' => substr($jsonResponse, 0, 200) . '...'
                ]);
                
                return $jsonResponse;
                
            } catch (\Exception $e) {
                Log::warning("⚠️ Tentativo {$attempt}/{$maxRetries} fallito", [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt
                ]);
                
                if ($attempt >= $maxRetries) {
                    // Invia email di alert dopo 10 tentativi falliti
                    $this->sendAlertEmail($e->getMessage());
                    throw new \Exception("Tutti i {$maxRetries} tentativi falliti. Ultimo errore: " . $e->getMessage());
                }
                
                // Attendi prima del prossimo tentativo (backoff esponenziale)
                $waitTime = min(pow(2, $attempt - 1), 30); // Max 30 secondi
                Log::info("⏳ Attendo {$waitTime} secondi prima del prossimo tentativo");
                sleep($waitTime);
                
                $attempt++;
            }
        }
        
        throw new \Exception("Errore imprevisto: tutti i tentativi esauriti");
    }

    /**
     * Carica il file PDF su OpenAI con retry
     */
    private function uploadFileToOpenAI(string $pdfPath): string
    {
        $maxRetries = 5;
        $attempt = 1;
        
        while ($attempt <= $maxRetries) {
            try {
                Log::info("📤 Tentativo {$attempt}/{$maxRetries} - Upload file su OpenAI");
                
                $response = Http::timeout(30)->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])->attach(
                    'file',
                    file_get_contents($pdfPath),
                    basename($pdfPath)
                )->post('https://api.openai.com/v1/files', [
                    'purpose' => 'assistants'
                ]);

                if (!$response->successful()) {
                    throw new \Exception('Errore upload file OpenAI: ' . $response->body());
                }

                $result = $response->json();
                $fileId = $result['id'] ?? '';
                
                Log::info("✅ File caricato su OpenAI al tentativo {$attempt}", [
                    'file_id' => $fileId,
                    'filename' => basename($pdfPath)
                ]);
                
                return $fileId;
                
            } catch (\Exception $e) {
                Log::warning("⚠️ Tentativo {$attempt}/{$maxRetries} upload fallito", [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt
                ]);
                
                if ($attempt >= $maxRetries) {
                    throw new \Exception("Upload file fallito dopo {$maxRetries} tentativi: " . $e->getMessage());
                }
                
                // Attendi prima del prossimo tentativo
                $waitTime = min(pow(2, $attempt - 1), 10); // Max 10 secondi
                Log::info("⏳ Attendo {$waitTime} secondi prima del prossimo tentativo upload");
                sleep($waitTime);
                
                $attempt++;
            }
        }
        
        throw new \Exception("Errore imprevisto: tutti i tentativi di upload esauriti");
    }

    /**
     * Costruisce il prompt per ChatGPT
     */
    private function buildPrompt(): string
    {
        return <<<PROMPT
Sei un parser fiscale esperto. Analizza il PDF F24 allegato e estrai TUTTI i dati presenti.

🚨 REGOLE CRITICHE - SEGUI ALLA LETTERA:

1. **ESTRAI TUTTI I DATI VISIBILI**: Non saltare nessuna riga con dati
2. **SEZIONI**: Rispetta le sezioni del documento (ERARIO, INPS, IMU, REGIONI)
3. **CODICI TRIBUTO**: Estrai sempre il codice tributo/causale
4. **IMPORTI**: Estrai sempre gli importi (converti virgola in punto: 747,74 → 747.74)
5. **DATE**: Estrai date di scadenza e periodi di riferimento
6. **MATRICOLE**: Estrai matricole INPS quando presenti

📋 STRUTTURA DETTAGLIATA DA SEGUIRE:

**SEZIONE INPS** (se presente):
- Cerca "SEZIONE INPS" o "INPS" nel documento
- Estrai: codice_sede, causale, matricola, periodo_da, periodo_a, importo_a_debito, importo_a_credito
- Esempio: se vedi "CF" come causale, "747,74" come importo, "01 2025" e "12 2025" come periodo → inserisci in inps[]
- Cerca anche "codice sede", "causale contributo", "matricola INPS", "periodo di riferimento"
- Se vedi "5600" come codice sede, "CF" come causale, "21540560251104882" come matricola → inserisci tutti questi dati

**SEZIONE ERARIO** (se presente):
- Cerca "SEZIONE ERARIO" o "ERARIO" nel documento  
- Estrai: codice_tributo, rateazione, anno_riferimento, importo_a_debito, importo_a_credito

**SEZIONE IMU** (se presente):
- Cerca "SEZIONE IMU" o "IMU" nel documento
- Estrai: codice_comune, codice_tributo, anno_riferimento, importo_a_debito, importo_a_credito

**DATA DI SCADENZA**:
- Cerca "SCADENZA:", "DA VERSARE ENTRO", "SCADENZA" nel documento
- Formato: YYYY-MM-DD
- Esempio: "SCADENZA: 16/02/2026" → "2026-02-16"

📌 CLASSIFICAZIONE OBBLIGATORIA:
- Codici CF, AF, CP, AP, CPI, CPR, CFP, AFP, CPP, APP → SEMPRE in "inps"
- Codici 3850, 3912-3919 → SEMPRE in "imu"  
- Codici 1668, 1669, 1790-1792, 8944, 1944, 1989, 1990 → SEMPRE in "erario"

⚠️ IMPORTANTE:
- Se vedi dati nella sezione INPS, DEVI inserirli in inps[]
- Se vedi dati nella sezione ERARIO, DEVI inserirli in erario[]
- NON lasciare sezioni vuote se ci sono dati visibili
- Estrai TUTTI i numeri e codici che vedi

📝 ESEMPIO COMPLETO (se vedi questi dati nell'F24):
- Data scadenza: "SCADENZA: 16/02/2026" → "due_date": "2026-02-16"
- Sezione INPS con: codice sede "5600", causale "CF", matricola "21540560251104882", periodo "01 2025" a "12 2025", importo "747,74"
→ "inps": [{"codice_sede": "5600", "causale": "CF", "matricola": "21540560251104882", "periodo_da": "01 2025", "periodo_a": "12 2025", "importo_a_debito": 747.74, "importo_a_credito": 0}]

📊 STRUTTURA JSON DA RISPETTARE:

{
  "due_date": "YYYY-MM-DD|null",
  "erario": [
    {
      "codice_tributo": "string",
      "rateazione": "string|null", 
      "anno_riferimento": "string|null",
      "importo_a_debito": number,
      "importo_a_credito": number
    }
  ],
  "inps": [
    {
      "codice_sede": "string",
      "causale": "string", 
      "matricola": "string",
      "periodo_da": "string",
      "periodo_a": "string",
      "importo_a_debito": number,
      "importo_a_credito": number
    }
  ],
  "imu": [
    {
      "codice_comune": "string",
      "codice_tributo": "string",
      "anno_riferimento": "string", 
      "importo_a_debito": number,
      "importo_a_credito": number
    }
  ],
  "altri": []
}

📌 OUTPUT: Restituisci SOLO JSON valido senza testo aggiuntivo
PROMPT;
    }

    /**
     * Parsa la risposta JSON di ChatGPT
     */
    private function parseJsonResponse(string $jsonResponse): array
    {
        try {
            $data = json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR);
            
            Log::info("✅ JSON parsato con successo", [
                'due_date' => $data['due_date'] ?? null,
                'erario_count' => count($data['erario'] ?? []),
                'inps_count' => count($data['inps'] ?? []),
                'imu_count' => count($data['imu'] ?? []),
                'altri_count' => count($data['altri'] ?? [])
            ]);
            
            // Correggi automaticamente eventuali errori di classificazione
            $data = $this->correctClassificationErrors($data);
            
            return $data;
            
        } catch (\JsonException $e) {
            Log::error("❌ Errore parsing JSON", [
                'error' => $e->getMessage(),
                'json_response' => $jsonResponse
            ]);
            return $this->getEmptyResponse();
        }
    }

    /**
     * Converte i dati parsati in record Tax
     */
    private function convertToTaxRecords(array $parsedData): array
    {
        $taxRecords = [
            'due_date' => $parsedData['due_date'] ?? null,
            'records' => []
        ];
        
        // Processa sezione Erario
        foreach ($parsedData['erario'] ?? [] as $erario) {
            if (!empty($erario['importo_a_debito']) || !empty($erario['importo_a_credito'])) {
                $taxRecords['records'][] = [
                    'type' => 'erario',
                    'codice_tributo' => $erario['codice_tributo'] ?? '',
                    'anno_riferimento' => $erario['anno_riferimento'] ?? null,
                    'importo' => $erario['importo_a_debito'] ?? $erario['importo_a_credito'] ?? 0,
                    'rateazione' => $erario['rateazione'] ?? null,
                    'raw_data' => $erario
                ];
            }
        }
        
        // Processa sezione INPS
        foreach ($parsedData['inps'] ?? [] as $inps) {
            if (!empty($inps['importo_a_debito']) || !empty($inps['importo_a_credito'])) {
                $periodoDa = $inps['periodo_da'] ?? '';
                $periodoA = $inps['periodo_a'] ?? '';
                $annoRiferimento = $this->extractYearFromPeriod($periodoDa ?: $periodoA);
                
                Log::info("📅 Estrazione anno INPS", [
                    'causale' => $inps['causale'] ?? '',
                    'periodo_da' => $periodoDa,
                    'periodo_a' => $periodoA,
                    'anno_estratto' => $annoRiferimento
                ]);
                
                $taxRecords['records'][] = [
                    'type' => 'inps',
                    'codice_tributo' => $inps['causale'] ?? '',
                    'anno_riferimento' => $annoRiferimento,
                    'importo' => $inps['importo_a_debito'] ?? $inps['importo_a_credito'] ?? 0,
                    'matricola' => $inps['matricola'] ?? null,
                    'raw_data' => $inps
                ];
            }
        }
        
        // Processa sezione IMU
        foreach ($parsedData['imu'] ?? [] as $imu) {
            if (!empty($imu['importo_a_debito']) || !empty($imu['importo_a_credito'])) {
                $taxRecords['records'][] = [
                    'type' => 'imu',
                    'codice_tributo' => $imu['codice_tributo'] ?? '',
                    'anno_riferimento' => $imu['anno_riferimento'] ?? null,
                    'importo' => $imu['importo_a_debito'] ?? $imu['importo_a_credito'] ?? 0,
                    'codice_comune' => $imu['codice_comune'] ?? null,
                    'raw_data' => $imu
                ];
            }
        }
        
        Log::info("🔄 Convertiti in record Tax", [
            'due_date' => $taxRecords['due_date'],
            'total_records' => count($taxRecords['records']),
            'types' => array_count_values(array_column($taxRecords['records'], 'type')),
            'records_complete' => $taxRecords['records']
        ]);
        
        return $taxRecords;
    }

    /**
     * Estrae l'anno da un periodo
     * Gestisce formati: "01/01/2022" → "2022", "012024" → "2024", "2024" → "2024"
     */
    private function extractYearFromPeriod(string $period): ?string
    {
        // Se il periodo è vuoto, ritorna null
        if (empty($period)) {
            return null;
        }
        
        // Formato mmYYYY (es. "012024" → "2024")
        if (preg_match('/^\d{2}(\d{4})$/', $period, $matches)) {
            return $matches[1];
        }
        
        // Formato YYYY (es. "2024" → "2024")
        if (preg_match('/^(\d{4})$/', $period, $matches)) {
            return $matches[1];
        }
        
        // Formato dd/mm/yyyy o dd-mm-yyyy (es. "01/01/2022" → "2022")
        if (preg_match('/(\d{4})/', $period, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Corregge automaticamente gli errori di classificazione
     */
    private function correctClassificationErrors(array $data): array
    {
        $corrections = [];
        
        // Codici INPS che potrebbero essere stati classificati erroneamente in erario
        $inpsCodes = ['CF', 'AF', 'CP', 'AP', 'CPI', 'CPR', 'CFP', 'AFP', 'CPP', 'APP'];
        
        // Controlla sezione erario per codici INPS
        if (isset($data['erario']) && is_array($data['erario'])) {
            foreach ($data['erario'] as $index => $item) {
                $codice = $item['codice_tributo'] ?? '';
                if (in_array($codice, $inpsCodes)) {
                    // Sposta da erario a inps
                    $data['inps'][] = $item;
                    unset($data['erario'][$index]);
                    $corrections[] = "Spostato codice {$codice} da erario a inps";
                }
            }
            // Riorganizza array erario
            $data['erario'] = array_values($data['erario']);
        }
        
        // Codici IMU che potrebbero essere stati classificati erroneamente
        $imuCodes = ['3850', '3912', '3913', '3914', '3915', '3916', '3917', '3918', '3919'];
        
        // Controlla sezione erario per codici IMU
        if (isset($data['erario']) && is_array($data['erario'])) {
            foreach ($data['erario'] as $index => $item) {
                $codice = $item['codice_tributo'] ?? '';
                if (in_array($codice, $imuCodes)) {
                    // Sposta da erario a imu
                    $data['imu'][] = $item;
                    unset($data['erario'][$index]);
                    $corrections[] = "Spostato codice {$codice} da erario a imu";
                }
            }
            // Riorganizza array erario
            $data['erario'] = array_values($data['erario']);
        }
        
        if (!empty($corrections)) {
            Log::info("🔧 Correzioni automatiche applicate", [
                'corrections' => $corrections,
                'final_counts' => [
                    'erario' => count($data['erario'] ?? []),
                    'inps' => count($data['inps'] ?? []),
                    'imu' => count($data['imu'] ?? [])
                ]
            ]);
        }
        
        return $data;
    }

    /**
     * Restituisce una risposta JSON vuota
     */
    private function getEmptyResponse(): array
    {
        return [
            'due_date' => null,
            'erario' => [],
            'inps' => [],
            'imu' => [],
            'altri' => []
        ];
    }

    /**
     * Invia email di alert per errori persistenti
     */
    private function sendAlertEmail(string $errorMessage): void
    {
        try {
            $subject = "🚨 ALERT: Errore persistente ChatGptF24ParserService";
            $message = "
            <h2>🚨 Errore persistente nel servizio F24 Parser</h2>
            
            <p><strong>Data/Ora:</strong> " . now()->format('Y-m-d H:i:s') . "</p>
            <p><strong>Errore:</strong> {$errorMessage}</p>
            <p><strong>Servizio:</strong> ChatGptF24ParserService</p>
            <p><strong>Max tentativi:</strong> 10</p>
            
            <p>Il servizio ha tentato 10 volte di chiamare l'API di ChatGPT ma tutti i tentativi sono falliti.</p>
            
            <p><strong>Azioni consigliate:</strong></p>
            <ul>
                <li>Verificare la connessione internet</li>
                <li>Controllare lo stato dell'API OpenAI</li>
                <li>Verificare la validità della API key</li>
                <li>Controllare i log per dettagli aggiuntivi</li>
            </ul>
            
            <p>Questo è un alert automatico generato dal sistema.</p>
            ";
            
            // Usa il sistema di email di Laravel
            \Mail::raw(strip_tags($message), function($mail) use ($subject, $message) {
                $mail->to('a.pisaroni@newo.io')
                     ->subject($subject)
                     ->html($message);
            });
            
            Log::error("📧 Email di alert inviata a a.pisaroni@newo.io", [
                'error' => $errorMessage
            ]);
            
        } catch (\Exception $e) {
            Log::error("❌ Errore nell'invio email di alert", [
                'error' => $e->getMessage(),
                'original_error' => $errorMessage
            ]);
        }
    }

    /**
     * Pulizia file temporanei
     */
    private function cleanup(): void
    {
        if (isset($this->tempDir) && !empty($this->tempDir) && file_exists($this->tempDir)) {
            shell_exec("rm -rf '{$this->tempDir}'");
        }
    }

    /**
     * Restituisce il prompt per debug
     */
    public function getPromptForDebug(): string
    {
        return $this->buildPrompt();
    }

    /**
     * Distruttore per pulizia automatica
     */
    public function __destruct()
    {
        $this->cleanup();
    }
}
