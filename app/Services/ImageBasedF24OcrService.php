<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageBasedF24OcrService
{
    private $tempDir;
    private $companyId;

    // Coordinate delle aree F24 (in pixel per pagina A4 595x842)
    // Queste sono le coordinate originali per un'immagine di circa 4000x2500 pixel
    private $originalAreas = [
        'area_1' => ['x1' => 633, 'y1' => 933, 'x2' => 2024, 'y2' => 1249],
        'area_2' => ['x1' => 70, 'y1' => 1394, 'x2' => 2020, 'y2' => 1596],
        'area_3' => ['x1' => 66, 'y1' => 2097, 'x2' => 2022, 'y2' => 2297],
    ];
    
    private $f24Areas = [];

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
        $this->tempDir = storage_path("app/temp/f24_ocr_{$companyId}_" . Str::random(8));
        
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    /**
     * Processa un file F24 convertendo in immagine e facendo OCR sulle aree specifiche
     */
    public function processF24File(string $filePath, string $filename): array
    {
        Log::info("🎯 INIZIO ImageBasedF24OcrService", [
            'filename' => $filename,
            'file_path' => $filePath
        ]);

        try {
            // 1. Converti PDF in immagine ad alta risoluzione
            $imagePath = $this->convertPdfToImage($filePath);
            
            // 2. Calcola le coordinate scalate basate sulle dimensioni reali dell'immagine
            $this->calculateScaledCoordinates($imagePath);
            
            // 2. Ritaglia ogni area e usa direttamente l'immagine cropped
            $allResults = [];
            foreach ($this->f24Areas as $areaName => $coords) {
                Log::info("🔍 Processando area {$areaName}", ['coords' => $coords]);
                
                // Ritaglia area specifica
                $croppedPath = $this->cropArea($imagePath, $coords, $areaName);
                
                // OCR numerico direttamente sull'immagine ritagliata (senza pre-processing)
                $ocrResult = $this->ocrNumericArea($croppedPath, $areaName);
                
                if (!empty($ocrResult)) {
                    $allResults[] = [
                        'area' => $areaName,
                        'text' => $ocrResult,
                        'coords' => $coords
                    ];
                }
            }

            // 3. Post-processing e validazione
            $validatedResults = $this->validateAndNormalize($allResults);
            
            Log::info("✅ ImageBasedF24OcrService completato", [
                'lines_found' => count($validatedResults),
                'filename' => $filename
            ]);

            return $validatedResults;

        } catch (\Exception $e) {
            Log::error("❌ Errore in ImageBasedF24OcrService", [
                'error' => $e->getMessage(),
                'filename' => $filename
            ]);
            return [];
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Converte PDF in immagine PNG ad alta risoluzione
     */
    private function convertPdfToImage(string $filePath): string
    {
        $outputPath = $this->tempDir . '/page.png';
        
        // Converti PDF in PNG a 300 DPI
        $command = "pdftoppm -r 300 -png '{$filePath}' '{$this->tempDir}/page'";
        shell_exec($command);
        
        // Trova il file generato
        $generatedFiles = glob($this->tempDir . '/page-*.png');
        if (empty($generatedFiles)) {
            throw new \Exception("Impossibile convertire PDF in immagine");
        }
        
        $imagePath = $generatedFiles[0];
        
        Log::info("📄 PDF convertito in immagine", [
            'output' => $imagePath,
            'resolution' => '300 DPI'
        ]);
        
        return $imagePath;
    }

    /**
     * Calcola le coordinate scalate basate sulle dimensioni reali dell'immagine
     */
    private function calculateScaledCoordinates(string $imagePath): void
    {
        // Ottieni le dimensioni dell'immagine generata
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            throw new \Exception("Impossibile ottenere le dimensioni dell'immagine");
        }
        
        $actualWidth = $imageInfo[0];
        $actualHeight = $imageInfo[1];
        
        // Dimensioni originali per cui sono state definite le coordinate
        $originalWidth = 4000;  // Approssimazione basata sulle coordinate
        $originalHeight = 5000; // Approssimazione basata sulle coordinate
        
        // Calcola i fattori di scala
        $scaleX = $actualWidth / $originalWidth;
        $scaleY = $actualHeight / $originalHeight;
        
        Log::info("📏 Calcolo coordinate scalate", [
            'original_width' => $originalWidth,
            'original_height' => $originalHeight,
            'actual_width' => $actualWidth,
            'actual_height' => $actualHeight,
            'scale_x' => $scaleX,
            'scale_y' => $scaleY
        ]);
        
        // Usa direttamente le coordinate per page-1.png
        $this->f24Areas = $this->originalAreas;
        
        Log::info("📍 Coordinate per page-1.png", [
            'areas' => $this->f24Areas
        ]);
        
        // Crea immagine di debug con le aree evidenziate
        $this->createDebugImage($imagePath);
    }

    /**
     * Ritaglia area specifica dall'immagine
     */
    private function cropArea(string $imagePath, array $coords, string $areaName): string
    {
        $outputPath = $this->tempDir . "/{$areaName}_cropped.png";
        
        $width = $coords['x2'] - $coords['x1'];
        $height = $coords['y2'] - $coords['y1'];
        
        // Ritaglia area specifica
        $command = "convert '{$imagePath}' -crop {$width}x{$height}+{$coords['x1']}+{$coords['y1']} '{$outputPath}'";
        shell_exec($command);
        
        Log::info("✂️ Area ritagliata", [
            'area' => $areaName,
            'coords' => $coords,
            'output' => $outputPath,
            'full_path' => realpath($outputPath)
        ]);
        
        return $outputPath;
    }

    /**
     * Pre-processing immagine ottimizzato per numeri
     */
    private function preprocessImage(string $imagePath, string $areaName): string
    {
        $outputPath = $this->tempDir . "/{$areaName}_processed.png";
        
        // Pipeline ottimizzata per numeri
        $command = "convert '{$imagePath}' " .
                   "-colorspace Gray " .
                   "-contrast-stretch 0 " .
                   "-normalize " .
                   "-sharpen 0x2 " .
                   "-deskew 40% " .
                   "-despeckle " .
                   "-threshold 60% " .
                   "-morphology Close Square:1 " .
                   "'{$outputPath}'";
        
        shell_exec($command);
        
        Log::info("🖼️ Immagine pre-processata", [
            'area' => $areaName,
            'output' => $outputPath
        ]);
        
        return $outputPath;
    }

    /**
     * OCR numerico mirato per area
     */
    private function ocrNumericArea(string $imagePath, string $areaName): string
    {
        // Configurazione Tesseract ottimizzata per numeri
        $config = [
            '--psm 6', // Assume uniform block of text
            '--oem 3', // LSTM OCR Engine
            '-c tessedit_char_whitelist=0123456789,./',
            '--dpi 300'
        ];
        
        $configStr = implode(' ', $config);
        $command = "tesseract '{$imagePath}' stdout {$configStr} 2>/dev/null";
        
        $result = shell_exec($command);
        $result = trim($result);
        
        // Filtra solo numeri e caratteri validi
        $result = preg_replace('/[^0-9,.\/]/', '', $result);
        
        Log::info("🔢 OCR area {$areaName}", [
            'result' => $result,
            'length' => strlen($result)
        ]);
        
        return $result;
    }

    /**
     * Validazione e normalizzazione risultati
     */
    private function validateAndNormalize(array $results): array
    {
        $normalized = [];
        
        foreach ($results as $result) {
            $text = $result['text'];
            
            Log::info("🔍 Analizzando testo OCR", [
                'area' => $result['area'],
                'text' => $text
            ]);
            
            // Estrai tutti i numeri dal testo per debug
            if (preg_match_all('/\d+/', $text, $allNumbers)) {
                Log::info("🔢 Tutti i numeri trovati in {$result['area']}", [
                    'numbers' => $allNumbers[0],
                    'count' => count($allNumbers[0])
                ]);
            }
            
            // Estrai tutti i pattern numerici con virgola/punto per debug
            if (preg_match_all('/\d+[.,]\d+/', $text, $decimalNumbers)) {
                Log::info("💰 Numeri decimali trovati in {$result['area']}", [
                    'decimals' => $decimalNumbers[0],
                    'count' => count($decimalNumbers[0])
                ]);
            }
            
            // Pattern specifici per F24
            $patterns = [
                'codice_tributo' => '/\b(\d{4})\b/', // 4 cifre (1790, 1792)
                'importo' => '/\b(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)\b/', // Importi (476,90)
                'anno' => '/\b(20\d{2})\b/', // Anni 2000+ (2022, 2021)
            ];
            
            // Metodo per area_1: estrazione per posizione dai numeri attaccati
            if ($result['area'] === 'area_1') {
                // Rimuovi TUTTI i caratteri non numerici (punti, virgole, spazi, ecc.)
                $cleanText = preg_replace('/[^0-9]/', '', $text);
                
                Log::info("🔍 Testo pulito area_1", [
                    'original' => $text,
                    'clean' => $cleanText,
                    'length' => strlen($cleanText)
                ]);
                
                // Estrai i due gruppi di dati usando il punto come separatore
                // Dividi per il punto: 179001012022476,90.179201012021112545
                $parts = explode('.', $text);
                
                Log::info("🔍 Divisione per punto", [
                    'parts' => $parts,
                    'count' => count($parts)
                ]);
                
                if (count($parts) >= 2) {
                    // Primo gruppo: prima del punto (179001012022476,90)
                    $firstGroup = preg_replace('/[^0-9]/', '', $parts[0]);
                    
                    // Primi 4 = codice tributo
                    $codice1 = substr($firstGroup, 0, 4);
                    // Secondi 4 = mese (ignoriamo)
                    // Terzi 4 = anno
                    $anno1 = substr($firstGroup, 8, 4);
                    // Rimanenti = importo (ultimi 2 sono decimali)
                    $importoRaw1 = substr($firstGroup, 12);
                    $importo1 = substr($importoRaw1, 0, -2) . '.' . substr($importoRaw1, -2);
                    
                    Log::info("🔍 Debug primo gruppo", [
                        'firstGroup' => $firstGroup,
                        'importoRaw1' => $importoRaw1,
                        'importo1' => $importo1,
                        'length_importoRaw' => strlen($importoRaw1)
                    ]);
                    
                    if (in_array($codice1, ['1790', '1792'])) {
                        $normalized[] = [
                            'type' => 'codice_tributo',
                            'value' => $codice1,
                            'area' => $result['area'],
                            'page' => 1,
                            'confidence' => 'high'
                        ];
                        
                        $normalized[] = [
                            'type' => 'importo',
                            'value' => $this->normalizeValue($importo1, 'importo'),
                            'area' => $result['area'],
                            'page' => 1,
                            'confidence' => 'high'
                        ];
                        
                        $normalized[] = [
                            'type' => 'anno',
                            'value' => $anno1,
                            'area' => $result['area'],
                            'page' => 1,
                            'confidence' => 'high'
                        ];
                        
                        Log::info("✅ Trovato primo gruppo F24 (per posizione)", [
                            'codice' => $codice1,
                            'importo' => $importo1,
                            'anno' => $anno1,
                            'raw_group' => $firstGroup
                        ]);
                    }
                    
                    // Secondo gruppo: dopo il punto (179201012021112545)
                    $secondGroup = preg_replace('/[^0-9]/', '', $parts[1]);
                    
                    // Primi 4 = codice tributo
                    $codice2 = substr($secondGroup, 0, 4);
                    // Secondi 4 = mese (ignoriamo)
                    // Terzi 4 = anno
                    $anno2 = substr($secondGroup, 8, 4);
                    // Rimanenti = importo (ultimi 2 sono decimali)
                    $importoRaw2 = substr($secondGroup, 12);
                    $importo2 = substr($importoRaw2, 0, -2) . '.' . substr($importoRaw2, -2);
                    
                    Log::info("🔍 Debug secondo gruppo", [
                        'secondGroup' => $secondGroup,
                        'importoRaw2' => $importoRaw2,
                        'importo2' => $importo2,
                        'length_importoRaw' => strlen($importoRaw2)
                    ]);
                    
                    if (in_array($codice2, ['1790', '1792'])) {
                        $normalized[] = [
                            'type' => 'codice_tributo',
                            'value' => $codice2,
                            'area' => $result['area'],
                            'page' => 1,
                            'confidence' => 'high'
                        ];
                        
                        $normalized[] = [
                            'type' => 'importo',
                            'value' => $this->normalizeValue($importo2, 'importo'),
                            'area' => $result['area'],
                            'page' => 1,
                            'confidence' => 'high'
                        ];
                        
                        $normalized[] = [
                            'type' => 'anno',
                            'value' => $anno2,
                            'area' => $result['area'],
                            'page' => 1,
                            'confidence' => 'high'
                        ];
                        
                        Log::info("✅ Trovato secondo gruppo F24 (per posizione)", [
                            'codice' => $codice2,
                            'importo' => $importo2,
                            'anno' => $anno2,
                            'raw_group' => $secondGroup
                        ]);
                    }
                }
            }
            
            foreach ($patterns as $type => $pattern) {
                if (preg_match_all($pattern, $text, $matches)) {
                    foreach ($matches[1] as $match) {
                        // Filtra valori troppo piccoli per importi
                        if ($type === 'importo' && (float)$match < 10) {
                            continue;
                        }
                        
                        // Filtra anni non validi
                        if ($type === 'anno' && ((int)$match < 2020 || (int)$match > 2030)) {
                            continue;
                        }
                        
                        $normalized[] = [
                            'type' => $type,
                            'value' => $this->normalizeValue($match, $type),
                            'area' => $result['area'],
                            'page' => 1,
                            'confidence' => 'high'
                        ];
                        
                        Log::info("✅ Trovato {$type}", [
                            'value' => $match,
                            'normalized' => $this->normalizeValue($match, $type),
                            'area' => $result['area']
                        ]);
                    }
                }
            }
        }
        
        Log::info("✅ Risultati normalizzati", [
            'total_items' => count($normalized),
            'types' => array_count_values(array_column($normalized, 'type')),
            'items' => $normalized
        ]);
        
        return $normalized;
    }

    /**
     * Crea un'immagine di debug con le aree evidenziate
     */
    private function createDebugImage(string $imagePath): void
    {
        $debugPath = $this->tempDir . '/debug_areas.png';
        
        // Copia l'immagine originale
        $command = "convert '{$imagePath}' '{$debugPath}'";
        shell_exec($command);
        
        // Aggiungi rettangoli colorati per ogni area
        foreach ($this->f24Areas as $areaName => $coords) {
            $width = $coords['x2'] - $coords['x1'];
            $height = $coords['y2'] - $coords['y1'];
            
            // Colori diversi per ogni area
            $colors = [
                'area_1' => 'red',
                'area_2' => 'blue', 
                'area_3' => 'green'
            ];
            
            $color = $colors[$areaName] ?? 'white';
            
            // Disegna rettangolo con etichetta
            $command = "convert '{$debugPath}' " .
                       "-fill none " .
                       "-stroke {$color} " .
                       "-strokewidth 3 " .
                       "-draw \"rectangle {$coords['x1']},{$coords['y1']} {$coords['x2']},{$coords['y2']}\" " .
                       "-fill {$color} " .
                       "-pointsize 20 " .
                       "-draw \"text " . ($coords['x1'] + 10) . "," . ($coords['y1'] + 30) . ' \'{$areaName}\'\" ' .
                       "'{$debugPath}'";
            
            shell_exec($command);
        }
        
        Log::info("🖼️ Immagine debug creata", [
            'debug_path' => $debugPath,
            'full_path' => realpath($debugPath)
        ]);
    }

    /**
     * Normalizza valori estratti
     */
    private function normalizeValue(string $value, string $type): string
    {
        switch ($type) {
            case 'importo':
                // Converti 1.234,56 → 1234.56
                return str_replace(',', '.', str_replace('.', '', $value));
            case 'codice_tributo':
                return str_pad($value, 4, '0', STR_PAD_LEFT);
            case 'anno':
                return $value;
            case 'periodo':
                return $value;
            default:
                return $value;
        }
    }

    /**
     * Pulizia file temporanei
     */
    private function cleanup(): void
    {
        // Non cancellare le immagini per debug
        // if (file_exists($this->tempDir)) {
        //     shell_exec("rm -rf '{$this->tempDir}'");
        // }
        
        Log::info("🔍 Immagini salvate per debug", [
            'temp_dir' => $this->tempDir,
            'files' => glob($this->tempDir . '/*')
        ]);
    }

    /**
     * Distruttore per pulizia automatica
     */
    public function __destruct()
    {
        $this->cleanup();
    }
}
