<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tax;
use Illuminate\Support\Facades\Log;

class UpdateExistingTaxesAsManual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taxes:update-existing-as-manual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggiorna le tasse esistenti caricate dagli F24 come manuali';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔄 Aggiornamento tasse esistenti caricate dagli F24...");
        
        // Aggiorna tutte le tasse che hanno un f24_id (caricate dagli F24)
        $updated = Tax::whereNotNull('f24_id')
            ->where('is_manual', false) // Solo quelle non ancora marcate
            ->update(['is_manual' => true]);
            
        $this->info("✅ Aggiornate {$updated} tasse caricate dagli F24 come manuali");
        
        // Mostra statistiche
        $totalTaxes = Tax::count();
        $manualTaxes = Tax::where('is_manual', true)->count();
        $automaticTaxes = Tax::where('is_manual', false)->count();
        
        $this->info("\n📊 Statistiche tasse:");
        $this->info("   - Totali: {$totalTaxes}");
        $this->info("   - Manuali: {$manualTaxes}");
        $this->info("   - Automatiche: {$automaticTaxes}");
        
        Log::info("Comando update-existing-as-manual completato", [
            'updated' => $updated,
            'total' => $totalTaxes,
            'manual' => $manualTaxes,
            'automatic' => $automaticTaxes
        ]);
        
        return 0;
    }
}
