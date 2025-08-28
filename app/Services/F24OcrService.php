<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
// Rimossa dipendenza TesseractOCR - usiamo exec() direttamente
use Exception;

class F24OcrService
{
    protected $tempPath;
    protected $supportedFormats = ['pdf', 'jpg', 'jpeg', 'png', 'tiff', 'bmp'];

    public function __construct()
    {
        $this->tempPath = storage_path('app/temp/ocr');
        if (!file_exists($this->tempPath)) {
            mkdir($this->tempPath, 0775, true);
            // Assicurati che i permessi siano corretti per il web server
            if (function_exists('chgrp')) {
                @chgrp($this->tempPath, 'www-data');
            }
            @chmod($this->tempPath, 0775);
        }
    }

    /**
     * Processa un file F24 e estrae i dati delle righe
     */
    public function processF24File($fileContent, $originalName, $mimeType): array
    {
        $extension = $this->getFileExtension($originalName, $mimeType);
        
        if (!in_array($extension, $this->supportedFormats)) {
            throw new Exception("Formato file non supportato: {$extension}");
        }

        $tempFilePath = $this->saveTempFile($fileContent, $extension);
        
        try {
            // Se è un PDF, convertilo in immagini
            if ($extension === 'pdf') {
                $imagePaths = $this->convertPdfToImages($tempFilePath);
                $f24Lines = [];
                
                foreach ($imagePaths as $imagePath) {
                    $pageLines = $this->processImageWithOcr($imagePath);
                    $f24Lines = array_merge($f24Lines, $pageLines);
                    unlink($imagePath); // Pulisci l'immagine temporanea
                }
            } else {
                // È già un'immagine
                $f24Lines = $this->processImageWithOcr($tempFilePath);
            }

            return $f24Lines;

        } finally {
            // Pulisci il file temporaneo
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
        }
    }

    /**
     * Converte PDF in immagini usando Poppler
     */
    protected function convertPdfToImages($pdfPath): array
    {
        $outputDir = dirname($pdfPath);
        $basename = pathinfo($pdfPath, PATHINFO_FILENAME);
        
        // Usa pdftoppm per convertire PDF in PNG
        $command = "pdftoppm -png -r 300 " . escapeshellarg($pdfPath) . " " . escapeshellarg($outputDir . '/' . $basename);
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Errore nella conversione PDF: " . implode("\n", $output));
        }

        // Trova tutti i file PNG generati
        $imagePaths = glob($outputDir . '/' . $basename . '-*.png');
        
        if (empty($imagePaths)) {
            throw new Exception("Nessuna immagine generata dal PDF");
        }

        return $imagePaths;
    }

    /**
     * Processa un'immagine con OCR e estrae i dati F24
     */
    protected function processImageWithOcr($imagePath): array
    {
        try {
            // Esegui OCR con Tesseract usando exec() direttamente
            $text = $this->runTesseractOcr($imagePath);
            
            Log::info("OCR Text estratto", [
                'file' => basename($imagePath),
                'text_length' => strlen($text),
                'text_preview' => substr($text, 0, 200)
            ]);

            // Analizza il testo e estrai le righe F24
            return $this->extractF24LinesFromText($text);

        } catch (Exception $e) {
            Log::error("Errore OCR", [
                'file' => $imagePath,
                'error' => $e->getMessage()
            ]);
            throw new Exception("Errore nell'elaborazione OCR: " . $e->getMessage());
        }
    }

    /**
     * Esegue Tesseract OCR usando exec() direttamente
     */
    protected function runTesseractOcr($imagePath): string
    {
        // Comando Tesseract con lingue italiano e inglese
        $outputFile = $imagePath . '_output';
        $command = sprintf(
            'tesseract %s %s -l ita+eng -c tessedit_char_whitelist="0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz.,/-€ " 2>&1',
            escapeshellarg($imagePath),
            escapeshellarg($outputFile)
        );
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Errore Tesseract OCR: " . implode("\n", $output));
        }
        
        // Leggi il file di output (Tesseract aggiunge automaticamente .txt)
        $textFile = $outputFile . '.txt';
        if (!file_exists($textFile)) {
            throw new Exception("File di output OCR non trovato: " . $textFile);
        }
        
        $text = file_get_contents($textFile);
        
        // Pulisci il file temporaneo
        if (file_exists($textFile)) {
            unlink($textFile);
        }
        
        return $text ?: '';
    }

    /**
     * Estrae le righe F24 dal testo OCR
     */
    protected function extractF24LinesFromText($text): array
    {
        $lines = [];
        $textLines = explode("\n", $text);
        
        // Pattern per identificare righe con codici tributo e importi
        $patterns = [
            // Pattern per righe standard F24: CODICE_TRIBUTO ... IMPORTO
            '/([A-Z0-9]{2,4})\s+.*?([0-9]{1,6}[,.]?[0-9]{0,2})\s*€?\s*$/i',
            // Pattern per importi in formato europeo: 1.234,56
            '/([A-Z0-9]{2,4})\s+.*?([0-9]{1,3}(?:\.[0-9]{3})*,[0-9]{2})\s*€?\s*$/i',
            // Pattern più specifico per F24
            '/codice\s+tributo[:\s]*([A-Z0-9]{2,4}).*?importo[:\s]*([0-9,. €]+)/i'
        ];

        foreach ($textLines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $codiceTributo = strtoupper(trim($matches[1]));
                    $importoStr = preg_replace('/[€\s]/', '', trim($matches[2]));
                    
                    // Converti importo in formato numerico
                    $importo = $this->parseImporto($importoStr);
                    
                    if ($importo > 0 && $this->isValidCodiceTributo($codiceTributo)) {
                        $lines[] = [
                            'codice_tributo' => $codiceTributo,
                            'importo' => $importo,
                            'anno_competenza' => $this->extractAnnoCompetenza($line),
                            'data_scadenza' => null, // Sarà calcolata dal servizio
                            'note' => $this->extractNote($line),
                            'raw_line' => $line
                        ];
                        
                        Log::info("Riga F24 estratta", [
                            'codice' => $codiceTributo,
                            'importo' => $importo,
                            'line' => $line
                        ]);
                    }
                    break; // Una volta trovato un pattern, passa alla prossima riga
                }
            }
        }

        // Se non trova righe con i pattern, prova a cercare manualmente numeri e codici
        if (empty($lines)) {
            $lines = $this->fallbackExtraction($text);
        }

        return $lines;
    }

    /**
     * Estrazione di fallback quando i pattern non funzionano
     */
    protected function fallbackExtraction($text): array
    {
        $lines = [];
        
        // Cerca tutti i possibili codici tributo
        preg_match_all('/\b([A-Z]{1,3}[0-9]{0,4}|[0-9]{4})\b/', $text, $codiciMatches);
        
        // Cerca tutti i possibili importi
        preg_match_all('/\b([0-9]{1,6}[,.]?[0-9]{0,2})\s*€?\b/', $text, $importiMatches);
        
        if (!empty($codiciMatches[1]) && !empty($importiMatches[1])) {
            $codici = array_filter($codiciMatches[1], [$this, 'isValidCodiceTributo']);
            $importi = array_map([$this, 'parseImporto'], $importiMatches[1]);
            $importi = array_filter($importi, function($i) { return $i > 0; });
            
            // Associa codici e importi (euristica semplice)
            $minCount = min(count($codici), count($importi));
            for ($i = 0; $i < $minCount; $i++) {
                $lines[] = [
                    'codice_tributo' => $codici[$i],
                    'importo' => $importi[$i],
                    'anno_competenza' => null,
                    'data_scadenza' => null,
                    'note' => 'Estratto automaticamente (verificare)',
                    'raw_line' => "Codice: {$codici[$i]}, Importo: {$importi[$i]}"
                ];
            }
        }

        return $lines;
    }

    /**
     * Converte stringa importo in float
     */
    protected function parseImporto($importoStr): float
    {
        // Rimuovi caratteri non numerici eccetto virgole e punti
        $clean = preg_replace('/[^0-9,.]/', '', $importoStr);
        
        // Gestisci formato europeo (1.234,56) vs americano (1,234.56)
        if (strpos($clean, ',') !== false && strpos($clean, '.') !== false) {
            // Se contiene sia virgola che punto, assume formato europeo
            $clean = str_replace('.', '', $clean); // Rimuovi separatori migliaia
            $clean = str_replace(',', '.', $clean); // Virgola come decimale
        } elseif (strpos($clean, ',') !== false) {
            // Solo virgola - potrebbe essere decimale italiano
            $parts = explode(',', $clean);
            if (count($parts) == 2 && strlen($parts[1]) <= 2) {
                $clean = $parts[0] . '.' . $parts[1];
            }
        }
        
        return (float) $clean;
    }

    /**
     * Verifica se il codice tributo è valido
     */
    protected function isValidCodiceTributo($codice): bool
    {
        $codiciValidi = [
            // Imposta sostitutiva
            '1790', '1791', '1792',
            // INPS Fissi e Percentuali
            'CF', 'AF', 'CFP', 'AFP', 'CP', 'AP', 'CPP', 'APP',
            // INPS aggiuntivi (CPI, CPR)
            'CPI', 'CPR',
            // CCIAA
            '3850',
            // Sanzioni e interessi
            '8944', '1944', '1989', '1990'
        ];
        
        return in_array($codice, $codiciValidi);
    }

    /**
     * Estrae l'anno di competenza dalla riga
     */
    protected function extractAnnoCompetenza($line): ?int
    {
        if (preg_match('/20[0-9]{2}/', $line, $matches)) {
            return (int) $matches[0];
        }
        
        return null;
    }

    /**
     * Estrae note dalla riga
     */
    protected function extractNote($line): string
    {
        // Rimuovi codice tributo e importo per ottenere la descrizione
        $note = preg_replace('/^[A-Z0-9]{2,4}\s+/', '', $line);
        $note = preg_replace('/[0-9,. €]+$/', '', $note);
        
        return trim($note) ?: 'Estratto da OCR';
    }

    /**
     * Determina l'estensione del file
     */
    protected function getFileExtension($filename, $mimeType): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (empty($extension)) {
            // Deduce dall'mime type
            $mimeToExt = [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/tiff' => 'tiff',
                'image/bmp' => 'bmp'
            ];
            
            $extension = $mimeToExt[$mimeType] ?? 'unknown';
        }
        
        return $extension;
    }

    /**
     * Salva il contenuto in un file temporaneo
     */
    protected function saveTempFile($content, $extension): string
    {
        $filename = uniqid('f24_') . '.' . $extension;
        $tempFilePath = $this->tempPath . '/' . $filename;
        
        if (file_put_contents($tempFilePath, $content) === false) {
            throw new Exception("Impossibile salvare il file temporaneo");
        }
        
        return $tempFilePath;
    }

    /**
     * Pulisce i file temporanei più vecchi di 1 ora
     */
    public function cleanupTempFiles(): void
    {
        $files = glob($this->tempPath . '/*');
        $cutoff = time() - 3600; // 1 ora fa
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
}
