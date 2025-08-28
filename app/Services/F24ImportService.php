<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Tax;
use App\Models\F24;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class F24ImportService
{
    /**
     * Importa i file F24 per una company
     */
    public function importF24Files(Company $company, array $f24Data): array
    {
        Log::info("🚀 INIZIO F24ImportService::importF24Files", [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'f24_data_keys' => array_keys($f24Data),
            'files_count' => count($f24Data['files'] ?? []),
            'payment_status' => $f24Data['payment_status'] ?? 'not_set',
            'skip_duplicates' => $f24Data['skip_duplicates'] ?? false,
            'auto_recalculate' => $f24Data['auto_recalculate'] ?? false
        ]);
        
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $skipDuplicates = $f24Data['skip_duplicates'] ?? false;
        
        $chatGptService = new ChatGptF24ParserService();
        Log::info("🤖 ChatGptF24ParserService creato con successo");

        foreach ($f24Data['files'] as $index => $fileData) {
            Log::info("📄 Elaborazione file F24", [
                'file_index' => $index,
                'filename' => $fileData['name'] ?? 'unknown',
                'mime_type' => $fileData['mime_type'] ?? 'unknown',
                'has_content' => isset($fileData['content']),
                'content_length' => isset($fileData['content']) ? strlen($fileData['content']) : 0
            ]);
            
            try {
                // Usa OCR per estrarre i dati dal file
                if (isset($fileData['content']) && isset($fileData['name']) && isset($fileData['mime_type'])) {
                    Log::info("🔍 Inizio decodifica base64 e OCR");
                    
                    // Decodifica il contenuto base64
                    $binaryContent = base64_decode($fileData['content']);
                    
                    if ($binaryContent === false) {
                        throw new Exception("Errore nella decodifica del file base64");
                    }
                    
                    Log::info("✅ Decodifica base64 completata", [
                        'binary_length' => strlen($binaryContent)
                    ]);
                    
                    // Usa ChatGPT per estrarre i dati dal file PRIMA di fare l'upload
                    $chatGptResults = $chatGptService->parseF24Content($binaryContent, $fileData['name']);
                    
                    // Controlla duplicati se richiesto
                    if ($skipDuplicates) {
                        $isDuplicate = $this->checkForDuplicates($company, $chatGptResults);
                        if ($isDuplicate) {
                            Log::info("🔄 F24 duplicato rilevato - saltato", [
                                'company_id' => $company->id,
                                'filename' => $fileData['name'],
                                'total_amount' => $this->calculateTotalAmount($chatGptResults)
                            ]);
                            $skipped++;
                            continue;
                        }
                    }
                    
                    // Upload su S3 solo se non è un duplicato
                    $s3Path = $this->uploadToS3($company, $binaryContent, $fileData['name']);
                    
                    // Crea il record F24
                    $f24Record = $this->createF24Record($company, $fileData['name'], $s3Path, $chatGptResults);
                    
                    // Crea i record Tax associati
                    $taxRecords = $this->createTaxRecords($company, $f24Record, $chatGptResults, $f24Data['payment_status'] ?? 'pending');
                    
                    Log::info("✅ F24 importato con successo", [
                        'company_id' => $company->id,
                        'f24_id' => $f24Record->id,
                        'filename' => $fileData['name'],
                        'tax_records_created' => count($taxRecords),
                        'total_amount' => $f24Record->total_amount
                    ]);
                    
                    $imported++;
                    
                } else {
                    Log::warning("⚠️ File senza dati - impossibile importare", [
                        'has_content' => isset($fileData['content']),
                        'has_name' => isset($fileData['name']),
                        'has_mime_type' => isset($fileData['mime_type'])
                    ]);
                    $skipped++;
                }
                
            } catch (Exception $e) {
                Log::error("❌ Errore importazione F24", [
                    'company_id' => $company->id,
                    'filename' => $fileData['name'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                $errors[] = [
                    'filename' => $fileData['name'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        Log::info("✅ Importazione F24 completata", [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => count($errors)
        ]);
        
        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    /**
     * Upload del file su S3
     */
    private function uploadToS3(Company $company, string $binaryContent, string $filename): string
    {
        $s3Path = "companies/{$company->id}/f24/" . date('Y/m/') . Str::uuid() . '_' . $filename;
        
        Storage::disk('s3')->put($s3Path, $binaryContent);
        
        Log::info("📤 File caricato su S3", [
            'company_id' => $company->id,
            'filename' => $filename,
            's3_path' => $s3Path,
            'file_size' => strlen($binaryContent)
        ]);
        
        return $s3Path;
    }

    /**
     * Crea il record F24
     */
    private function createF24Record(Company $company, string $filename, string $s3Path, array $chatGptResults): F24
    {
        $dueDate = null;
        if (!empty($chatGptResults['due_date'])) {
            try {
                $dueDate = \Carbon\Carbon::parse($chatGptResults['due_date'])->format('Y-m-d');
            } catch (Exception $e) {
                Log::warning("⚠️ Data di scadenza non valida", [
                    'due_date' => $chatGptResults['due_date'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Calcola il totale e le sezioni
        $totalAmount = $this->calculateTotalAmount($chatGptResults);
        $sections = [];
        $referenceYears = [];
        
        foreach ($chatGptResults['records'] ?? [] as $record) {
            if (!in_array($record['type'], $sections)) {
                $sections[] = $record['type'];
            }
            
            if (!empty($record['anno_riferimento']) && !in_array($record['anno_riferimento'], $referenceYears)) {
                $referenceYears[] = $record['anno_riferimento'];
            }
        }

        $f24Record = F24::create([
            'company_id' => $company->id,
            'filename' => $filename,
            's3_path' => $s3Path,
            's3_url' => Storage::disk('s3')->url($s3Path),
            'total_amount' => $totalAmount,
            'due_date' => $dueDate,
            'payment_status' => F24::STATUS_PENDING,
            'sections' => $sections,
            'reference_years' => $referenceYears,
            'notes' => "Importato automaticamente da file F24",
            'imported_at' => now()
        ]);

        Log::info("📋 Record F24 creato", [
            'f24_id' => $f24Record->id,
            'total_amount' => $totalAmount,
            'sections' => $sections,
            'reference_years' => $referenceYears,
            'due_date' => $dueDate
        ]);

        return $f24Record;
    }

    /**
     * Crea i record Tax associati al F24
     */
    private function createTaxRecords(Company $company, F24 $f24Record, array $chatGptResults, string $paymentStatus): array
    {
        $taxRecords = [];
        
        foreach ($chatGptResults['records'] ?? [] as $record) {
            try {
                $taxRecord = Tax::create([
                    'company_id' => $company->id,
                    'f24_id' => $f24Record->id,
                    'section_type' => $record['type'],
                    'tax_year' => $record['anno_riferimento'] ?? date('Y'),
                    'payment_year' => date('Y'),
                    'tax_type' => $this->mapTaxType($record['type'], $record['codice_tributo']),
                    'description' => $this->generateDescription($record),
                    'tax_code' => $record['codice_tributo'],
                    'amount' => $record['importo'],
                    'due_date' => $f24Record->due_date,
                    'payment_status' => $paymentStatus,
                    'notes' => "Estratto da F24: {$f24Record->filename}",
                    'is_manual' => true, // Marca come caricata manualmente
                ]);

                $taxRecords[] = $taxRecord;
                
                Log::info("💰 Record Tax creato da F24", [
                    'company_id' => $company->id,
                    'tax_id' => $taxRecord->id,
                    'tax_code' => $record['codice_tributo'],
                    'amount' => $record['importo'],
                    'payment_status' => $paymentStatus
                ]);

            } catch (Exception $e) {
                Log::error("❌ Errore creazione record Tax", [
                    'company_id' => $company->id,
                    'f24_id' => $f24Record->id,
                    'record' => $record,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $taxRecords;
    }

    /**
     * Mappa il tipo di tassa in base alla sezione e al codice tributo
     */
    private function mapTaxType(string $section, string $codiceTributo): string
    {
        switch ($section) {
            case 'erario':
                switch ($codiceTributo) {
                    case '1790':
                        return Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO;
                    case '1791':
                        return Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO;
                    case '1792':
                        return Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SALDO;
                    case '3850':
                        return Tax::TAX_TYPE_DIRITTO_ANNUALE_CCIAA;
                    case '8944':
                        return Tax::TAX_TYPE_SANZIONI;
                    case '1944':
                        return Tax::TAX_TYPE_INTERESSI;
                    default:
                        return Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SALDO;
                }
                
            case 'inps':
                switch ($codiceTributo) {
                    case 'CPI':
                    case 'CPR':
                        return Tax::TAX_TYPE_INPS_PERCENTUALI_SALDO;
                    case 'CF':
                    case 'AF':
                        return Tax::TAX_TYPE_INPS_FISSI_SALDO;
                    default:
                        return Tax::TAX_TYPE_INPS_SALDO;
                }
                
            case 'imu':
                return Tax::TAX_TYPE_DIRITTO_ANNUALE_CCIAA;
                
            default:
                return Tax::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SALDO;
        }
    }

    /**
     * Genera una descrizione per il record Tax
     */
    private function generateDescription(array $record): string
    {
        $section = ucfirst($record['type']);
        $codice = $record['codice_tributo'];
        $anno = $record['anno_riferimento'] ?? '';
        
        return "{$section} - Codice {$codice}" . ($anno ? " - Anno {$anno}" : '');
    }

    /**
     * Controlla se esiste già un F24 con le stesse tasse
     */
    private function checkForDuplicates(Company $company, array $chatGptResults): bool
    {
        $totalAmount = $this->calculateTotalAmount($chatGptResults);
        $newTaxes = $chatGptResults['records'] ?? [];
        
        Log::info("🔍 Controllo duplicati", [
            'company_id' => $company->id,
            'total_amount' => $totalAmount,
            'new_taxes_count' => count($newTaxes),
            'new_taxes' => array_map(function($tax) {
                return [
                    'type' => $tax['type'] ?? 'unknown',
                    'codice_tributo' => $tax['codice_tributo'] ?? 'unknown',
                    'importo' => $tax['importo'] ?? 0,
                    'anno_riferimento' => $tax['anno_riferimento'] ?? 'unknown'
                ];
            }, $newTaxes)
        ]);
        
        // Cerca F24 con lo stesso importo totale
        $existingF24s = F24::where('company_id', $company->id)
            ->where('total_amount', $totalAmount)
            ->with(['taxes'])
            ->get();
        
        Log::info("🔍 F24 esistenti con stesso importo", [
            'count' => $existingF24s->count(),
            'f24_ids' => $existingF24s->pluck('id')->toArray()
        ]);
        
        if ($existingF24s->isEmpty()) {
            Log::info("✅ Nessun F24 esistente con lo stesso importo - non è un duplicato");
            return false;
        }
        
        // Per ogni F24 esistente con lo stesso importo, controlla se ha le stesse tasse
        foreach ($existingF24s as $existingF24) {
            Log::info("🔍 Controllo F24 esistente", [
                'f24_id' => $existingF24->id,
                'filename' => $existingF24->filename,
                'existing_taxes_count' => $existingF24->taxes->count()
            ]);
            
            if ($this->hasSameTaxes($existingF24, $chatGptResults)) {
                Log::info("🔄 Duplicato trovato", [
                    'existing_f24_id' => $existingF24->id,
                    'existing_filename' => $existingF24->filename,
                    'total_amount' => $totalAmount,
                    'taxes_count' => count($newTaxes)
                ]);
                return true;
            }
        }
        
        Log::info("✅ Nessun duplicato trovato");
        return false;
    }

    /**
     * Calcola l'importo totale dai risultati di ChatGPT
     */
    private function calculateTotalAmount(array $chatGptResults): float
    {
        $totalAmount = 0;
        
        foreach ($chatGptResults['records'] ?? [] as $record) {
            $totalAmount += $record['importo'] ?? 0;
        }
        
        return $totalAmount;
    }

    /**
     * Controlla se un F24 esistente ha le stesse tasse di quello nuovo
     */
    private function hasSameTaxes(F24 $existingF24, array $chatGptResults): bool
    {
        $existingTaxes = $existingF24->taxes;
        $newTaxes = $chatGptResults['records'] ?? [];
        
        Log::info("🔍 Confronto tasse", [
            'existing_taxes_count' => $existingTaxes->count(),
            'new_taxes_count' => count($newTaxes)
        ]);
        
        // Se il numero di tasse è diverso, non sono uguali
        if (count($existingTaxes) !== count($newTaxes)) {
            Log::info("❌ Numero di tasse diverso", [
                'existing_count' => $existingTaxes->count(),
                'new_count' => count($newTaxes)
            ]);
            return false;
        }
        
        // Crea un array di chiavi per le tasse esistenti
        $existingTaxKeys = [];
        foreach ($existingTaxes as $tax) {
            $key = $this->createTaxKey($tax);
            $existingTaxKeys[$key] = ($existingTaxKeys[$key] ?? 0) + 1;
        }
        
        Log::info("🔍 Chiavi tasse esistenti", [
            'keys' => array_keys($existingTaxKeys),
            'counts' => array_values($existingTaxKeys)
        ]);
        
        // Controlla le tasse nuove
        foreach ($newTaxes as $newTax) {
            $key = $this->createTaxKeyFromRecord($newTax);
            Log::info("🔍 Controllo tassa nuova", [
                'key' => $key,
                'exists' => isset($existingTaxKeys[$key]),
                'remaining_count' => $existingTaxKeys[$key] ?? 0
            ]);
            
            if (!isset($existingTaxKeys[$key]) || $existingTaxKeys[$key] <= 0) {
                Log::info("❌ Tassa nuova non trovata o esaurita", [
                    'key' => $key,
                    'available_keys' => array_keys($existingTaxKeys)
                ]);
                return false;
            }
            $existingTaxKeys[$key]--;
        }
        
        // Controlla che tutte le tasse esistenti siano state utilizzate
        foreach ($existingTaxKeys as $key => $count) {
            if ($count !== 0) {
                Log::info("❌ Tasse esistenti non completamente utilizzate", [
                    'key' => $key,
                    'remaining_count' => $count
                ]);
                return false;
            }
        }
        
        Log::info("✅ Tasse identiche trovate");
        return true;
    }

    /**
     * Crea una chiave unica per una tassa esistente
     */
    private function createTaxKey(Tax $tax): string
    {
        $key = sprintf(
            '%s_%s_%s_%.2f',
            $tax->section_type,
            $tax->tax_code,
            $tax->tax_year,
            $tax->amount
        );
        
        Log::info("🔑 Chiave tassa esistente creata", [
            'tax_id' => $tax->id,
            'section_type' => $tax->section_type,
            'tax_code' => $tax->tax_code,
            'tax_year' => $tax->tax_year,
            'amount' => $tax->amount,
            'key' => $key
        ]);
        
        return $key;
    }

    /**
     * Crea una chiave unica per una tassa dai risultati di ChatGPT
     */
    private function createTaxKeyFromRecord(array $record): string
    {
        $anno = $record['anno_riferimento'] ?? date('Y');
        $importo = $record['importo'] ?? 0;
        
        $key = sprintf(
            '%s_%s_%s_%.2f',
            $record['type'],
            $record['codice_tributo'],
            $anno,
            $importo
        );
        
        Log::info("🔑 Chiave tassa nuova creata", [
            'type' => $record['type'],
            'codice_tributo' => $record['codice_tributo'],
            'anno_riferimento' => $anno,
            'importo' => $importo,
            'key' => $key
        ]);
        
        return $key;
    }
}
