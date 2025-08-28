<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Aumenta la precisione delle aliquote INPS per includere più decimali
        Schema::table('inps_parameters', function (Blueprint $table) {
            // Cambia da decimal(5,4) a decimal(8,5) per avere precisione fino al millesimo
            $table->decimal('aliquota_commercianti', 8, 5)->change();
            $table->decimal('aliquota_commercianti_ridotta', 8, 5)->change();
            $table->decimal('aliquota_artigiani', 8, 5)->change();
            $table->decimal('aliquota_artigiani_ridotta', 8, 5)->change();
            $table->decimal('aliquota_gestione_separata', 8, 5)->change();
            $table->decimal('aliquota_gestione_separata_ridotta', 8, 5)->change();
            
            // Anche le aliquote maggiorate
            $table->decimal('aliquota_commercianti_maggiorata', 8, 5)->change();
            $table->decimal('aliquota_commercianti_maggiorata_ridotta', 8, 5)->change();
            $table->decimal('aliquota_artigiani_maggiorata', 8, 5)->change();
            $table->decimal('aliquota_artigiani_maggiorata_ridotta', 8, 5)->change();
        });
        
        // Aggiorna i valori per il 2024 con le aliquote corrette
        DB::table('inps_parameters')
            ->where('year', 2024)
            ->update([
                // COMMERCIANTI - Valori ufficiali corretti
                'aliquota_commercianti' => 0.24480, // 24,480%
                'aliquota_commercianti_ridotta' => 0.15912, // 15,912% (24,480% × 65%)
                'aliquota_commercianti_maggiorata' => 0.25480, // 25,480% 
                'aliquota_commercianti_maggiorata_ridotta' => 0.16562, // 16,562% (25,480% × 65%)
                
                // ARTIGIANI - Da verificare se hanno le stesse soglie
                'aliquota_artigiani' => 0.24000, // 24,000%
                'aliquota_artigiani_ridotta' => 0.15600, // 15,600% (24,000% × 65%)
                'aliquota_artigiani_maggiorata' => 0.25000, // 25,000% (stima)
                'aliquota_artigiani_maggiorata_ridotta' => 0.16250, // 16,250% (25,000% × 65%)
                
                // Soglia per l'aliquota maggiorata (commercianti)
                'soglia_aliquota_maggiorata' => 55008.00, // €55.008
            ]);
            
        // Aggiorna anche il 2025 con valori stimati
        DB::table('inps_parameters')
            ->where('year', 2025)
            ->update([
                // COMMERCIANTI 2025 (valori stimati)
                'aliquota_commercianti' => 0.24480, // Manteniamo gli stessi del 2024
                'aliquota_commercianti_ridotta' => 0.15912,
                'aliquota_commercianti_maggiorata' => 0.25480,
                'aliquota_commercianti_maggiorata_ridotta' => 0.16562,
                
                // ARTIGIANI 2025
                'aliquota_artigiani' => 0.24000,
                'aliquota_artigiani_ridotta' => 0.15600,
                'aliquota_artigiani_maggiorata' => 0.25000,
                'aliquota_artigiani_maggiorata_ridotta' => 0.16250,
                
                // Soglia (probabilmente aggiornata per il 2025)
                'soglia_aliquota_maggiorata' => 55500.00, // Stima
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inps_parameters', function (Blueprint $table) {
            // Torna alla precisione precedente
            $table->decimal('aliquota_commercianti', 5, 4)->change();
            $table->decimal('aliquota_commercianti_ridotta', 5, 4)->change();
            $table->decimal('aliquota_artigiani', 5, 4)->change();
            $table->decimal('aliquota_artigiani_ridotta', 5, 4)->change();
            $table->decimal('aliquota_gestione_separata', 5, 4)->change();
            $table->decimal('aliquota_gestione_separata_ridotta', 5, 4)->change();
            $table->decimal('aliquota_commercianti_maggiorata', 5, 4)->change();
            $table->decimal('aliquota_commercianti_maggiorata_ridotta', 5, 4)->change();
            $table->decimal('aliquota_artigiani_maggiorata', 5, 4)->change();
            $table->decimal('aliquota_artigiani_maggiorata_ridotta', 5, 4)->change();
        });
    }
};