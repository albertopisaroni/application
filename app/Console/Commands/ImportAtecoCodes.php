<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportAtecoCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Qui puoi passare come argomento il percorso del file.
     */
    protected $signature = 'ateco:import {file : Percorso del file txt da importare}';

    /**
     * The console command description.
     */
    protected $description = 'Importa i codici ATECO da un file di testo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!File::exists($filePath)) {
            $this->error("File non trovato: {$filePath}");
            return 1;
        }

        // Legge il contenuto del file
        $contents = File::get($filePath);

        // Separa il file in righe
        $lines = preg_split('/\r\n|\r|\n/', $contents);

        $inserted = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Salta righe che non iniziano con un numero (ad esempio intestazioni tipo "A AGRICOLTURA...")
            if (!preg_match('/^(\d+(?:\.\d+)*)(\s+)(.*)$/', $line, $matches)) {
                $skipped++;
                continue;
            }
            
            $code = trim($matches[1]);
            $description = trim($matches[3]);
            
            // Inserisci nel database se il codice non esiste già
            $exists = DB::table('ateco_codes')->where('code', $code)->exists();
            if (!$exists) {
                DB::table('ateco_codes')->insert([
                    'code' => $code,
                    'description' => $description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $inserted++;
            } else {
                $skipped++;
            }
        }

        $this->info("Importazione completata. Inseriti: $inserted, Saltati: $skipped");

        return 0;
    }
}