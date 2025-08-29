<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ChatGptF24ParserService;
use Illuminate\Support\Facades\Log;

class TestF24Parsing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'f24:test-parsing {file : Path del file PDF da testare}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa il parsing di un F24 con ChatGPT';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("File non trovato: {$filePath}");
            return 1;
        }
        
        $this->info("🧪 Test parsing F24: {$filePath}");
        
        try {
            $parser = new ChatGptF24ParserService();
            $pdfContent = file_get_contents($filePath);
            
            $this->info("📄 File caricato, dimensione: " . strlen($pdfContent) . " bytes");
            
            $result = $parser->parseF24Content($pdfContent, basename($filePath));
            
            $this->info("\n✅ Risultato parsing:");
            $this->info("Data scadenza: " . ($result['due_date'] ?? 'N/A'));
            $this->info("Record totali: " . count($result['records'] ?? []));
            
            if (!empty($result['records'])) {
                $this->info("\n📋 Record estratti:");
                foreach ($result['records'] as $index => $record) {
                    $this->info("Record " . ($index + 1) . ":");
                    $this->info("  - Tipo: " . $record['type']);
                    $this->info("  - Codice: " . ($record['codice_tributo'] ?? 'N/A'));
                    $this->info("  - Importo: " . ($record['importo'] ?? 'N/A'));
                    $this->info("  - Anno: " . ($record['anno_riferimento'] ?? 'N/A'));
                    if (isset($record['matricola'])) {
                        $this->info("  - Matricola: " . $record['matricola']);
                    }
                    $this->info("");
                }
            } else {
                $this->warn("⚠️ Nessun record estratto dal file");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Errore durante il parsing: " . $e->getMessage());
            Log::error("Errore test parsing F24", [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
