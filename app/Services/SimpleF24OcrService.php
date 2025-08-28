<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SimpleF24OcrService
{
    private $companyId;

    // Coordinate delle aree F24 (in pixel per pagina A4 595x842)
    private $f24Areas = [
        'area_1' => ['x1' => 1065, 'y1' => 1665, 'x2' => 3962, 'y2' => 2421],
        'area_2' => ['x1' => -4, 'y1' => 2569, 'x2' => 3941, 'y2' => 3126],
        'area_3' => ['x1' => 45, 'y1' => 3230, 'x2' => 3958, 'y2' => 3816],
        'area_4' => ['x1' => 45, 'y1' => 3993, 'x2' => 3954, 'y2' => 4505],
    ];

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    /**
     * Processa un file F24 usando approccio semplificato
     */
    public function processF24File(string $filePath, string $filename): array
    {
        Log::info("🎯 INIZIO SimpleF24OcrService", [
            'filename' => $filename,
            'file_path' => $filePath
        ]);

        try {
            // Leggi il contenuto del file
            $content = file_get_contents($filePath);
            
            if ($content === false) {
                throw new \Exception("Impossibile leggere il file");
            }

            // Estrai pattern numerici dal contenuto binario
            $results = $this->extractPatternsFromContent($content);
            
            // Analizza anche le aree specifiche F24
            $areaResults = $this->extractFromSpecificAreas($content);
            $results = array_merge($results, $areaResults);
            
            Log::info("✅ SimpleF24OcrService completato", [
                'lines_found' => count($results),
                'filename' => $filename
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error("❌ Errore in SimpleF24OcrService", [
                'error' => $e->getMessage(),
                'filename' => $filename
            ]);
            return [];
        }
    }

    /**
     * Estrae pattern numerici dal contenuto del file
     */
    private function extractPatternsFromContent(string $content): array
    {
        $results = [];
        
        // Converti il contenuto in stringa per cercare pattern
        $text = $this->binaryToString($content);
        
        // Pattern per codici tributo (numerici e alfanumerici)
        $codicePatterns = [
            '/\b(\d{4})\b/', // 4 cifre (1790, 1791, 1792, etc.)
            '/\b([A-Z]{2,3})\b/', // 2-3 lettere (CF, CP, CPP, etc.)
        ];
        
        foreach ($codicePatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $match) {
                    // Filtra solo codici tributo validi
                    if ($this->isValidCodiceTributo($match)) {
                        $results[] = [
                            'type' => 'codice_tributo',
                            'value' => $match,
                            'area' => 'content_scan',
                            'page' => 1,
                            'confidence' => 'medium'
                        ];
                    }
                }
            }
        }
        
        // Pattern per importi (numeri con decimali)
        if (preg_match_all('/\b(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)\b/', $text, $matches)) {
            foreach ($matches[1] as $match) {
                $normalized = $this->normalizeAmount($match);
                if ($normalized > 0) {
                    $results[] = [
                        'type' => 'importo',
                        'value' => $normalized,
                        'area' => 'content_scan',
                        'page' => 1,
                        'confidence' => 'medium'
                    ];
                }
            }
        }
        
        // Pattern per anni (2000+)
        if (preg_match_all('/\b(20\d{2})\b/', $text, $matches)) {
            foreach ($matches[1] as $match) {
                $year = (int)$match;
                if ($year >= 2000 && $year <= date('Y') + 1) {
                    $results[] = [
                        'type' => 'anno',
                        'value' => $match,
                        'area' => 'content_scan',
                        'page' => 1,
                        'confidence' => 'high'
                    ];
                }
            }
        }
        
        // Pattern specifici per F24
        $f24Patterns = [
            'codice_tributo' => [
                '/\b(179[0-2])\b/', // Imposte sostitutive
                '/\b(166[8-9])\b/', // Imposte sostitutive
                '/\b(CF|AF|CFP|AFP)\b/', // INPS fissi
                '/\b(CP|AP|CPP|APP|CPI|CPR)\b/', // INPS percentuali
                '/\b(3850)\b/', // CCIAA
                '/\b(8944|1944|1989|1990)\b/', // Sanzioni e interessi
            ]
        ];
        
        foreach ($f24Patterns['codice_tributo'] as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $match) {
                    $results[] = [
                        'type' => 'codice_tributo',
                        'value' => $match,
                        'area' => 'f24_specific',
                        'page' => 1,
                        'confidence' => 'high'
                    ];
                }
            }
        }
        
        // Debug: mostra il testo estratto per analisi
        $textPreview = substr($text, 0, 1000);
        Log::info("🔍 Testo estratto (primi 1000 caratteri)", ['preview' => $textPreview]);
        
        Log::info("🔍 Pattern estratti", [
            'total_patterns' => count($results),
            'codici_tributo' => count(array_filter($results, fn($r) => $r['type'] === 'codice_tributo')),
            'importi' => count(array_filter($results, fn($r) => $r['type'] === 'importo')),
            'anni' => count(array_filter($results, fn($r) => $r['type'] === 'anno')),
            'text_length' => strlen($text)
        ]);
        
        return $results;
    }

    /**
     * Estrae dati dalle aree specifiche F24
     */
    private function extractFromSpecificAreas(string $content): array
    {
        $results = [];
        $text = $this->binaryToString($content);
        
        Log::info("🎯 Analisi pattern F24 specifici", [
            'text_length' => strlen($text)
        ]);
        
        // Cerca pattern specifici degli F24
        $f24Patterns = $this->extractF24SpecificPatterns($text);
        
        Log::info("🔢 Pattern F24 trovati", [
            'patterns_count' => count($f24Patterns),
            'patterns' => array_slice($f24Patterns, 0, 10) // Primi 10 pattern
        ]);
        
        foreach ($f24Patterns as $pattern) {
            $results[] = [
                'type' => $pattern['type'],
                'value' => $pattern['value'],
                'area' => 'f24_pattern',
                'page' => 1,
                'confidence' => 'high'
            ];
        }
        
        return $results;
    }

    /**
     * Estrae pattern specifici degli F24
     */
    private function extractF24SpecificPatterns(string $text): array
    {
        $patterns = [];
        
        // Cerca codici tributo con importi associati
        // Pattern: codice_tributo + importo (es: 1790 476.90)
        if (preg_match_all('/(\d{4})\s+(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $codice = $match[1];
                $importo = $this->normalizeAmount($match[2]);
                
                if ($this->isValidCodiceTributo($codice) && $importo > 0) {
                    $patterns[] = [
                        'type' => 'codice_importo',
                        'value' => "{$codice}:{$importo}",
                        'codice' => $codice,
                        'importo' => $importo
                    ];
                }
            }
        }
        
        // Cerca anni con importi (es: 2022 476.90)
        if (preg_match_all('/(20\d{2})\s+(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $anno = $match[1];
                $importo = $this->normalizeAmount($match[2]);
                
                if ($importo > 0) {
                    $patterns[] = [
                        'type' => 'anno_importo',
                        'value' => "{$anno}:{$importo}",
                        'anno' => $anno,
                        'importo' => $importo
                    ];
                }
            }
        }
        
        // Cerca importi significativi (> 100€)
        if (preg_match_all('/(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/', $text, $matches)) {
            foreach ($matches[1] as $match) {
                $importo = $this->normalizeAmount($match);
                if ($importo >= 100 && $importo <= 10000) { // Importi realistici F24
                    $patterns[] = [
                        'type' => 'importo_significativo',
                        'value' => $importo,
                        'importo' => $importo
                    ];
                }
            }
        }
        
        return $patterns;
    }



    /**
     * Converte contenuto binario in stringa leggibile
     */
    private function binaryToString(string $content): string
    {
        // Rimuovi caratteri non stampabili
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
        
        // Converti in UTF-8 se possibile
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
        }
        
        return $text;
    }

    /**
     * Verifica se un codice tributo è valido
     */
    private function isValidCodiceTributo(string $code): bool
    {
        $validCodes = [
            '1668', '1669', '1790', '1791', '1792', // Imposte sostitutive
            'CF', 'AF', 'CFP', 'AFP', // INPS fissi
            'CP', 'AP', 'CPP', 'APP', 'CPI', 'CPR', // INPS percentuali
            '3850', // CCIAA
            '8944', '1944', '1989', '1990' // Sanzioni e interessi
        ];
        
        return in_array($code, $validCodes);
    }

    /**
     * Normalizza un importo
     */
    private function normalizeAmount(string $amount): float
    {
        // Rimuovi spazi e caratteri non numerici
        $clean = preg_replace('/[^\d,.]/', '', $amount);
        
        // Gestisci formati diversi
        if (strpos($clean, ',') !== false && strpos($clean, '.') !== false) {
            // Formato italiano: 1.234,56
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (strpos($clean, ',') !== false) {
            // Solo virgola: 1234,56
            $clean = str_replace(',', '.', $clean);
        }
        
        $value = (float)$clean;
        
        // Filtra valori non realistici
        if ($value < 0.01 || $value > 1000000) {
            return 0;
        }
        
        return $value;
    }
}
