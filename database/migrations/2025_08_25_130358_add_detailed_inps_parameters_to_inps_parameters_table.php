<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inps_parameters', function (Blueprint $table) {
            // Addizionale IVS (es. 0.09% per alcuni anni)
            $table->decimal('addizionale_ivs_percentuale', 5, 4)->default(0)->after('aliquota_gestione_separata_ridotta');
            
            // Massimale reddituale (oltre questo si applica maggiorazione)
            $table->decimal('massimale_reddituale', 10, 2)->default(0)->after('massimale_commercianti_artigiani');
            
            // Maggiorazione aliquota oltre massimale (es. 1% extra)
            $table->decimal('maggiorazione_oltre_massimale', 5, 4)->default(0)->after('massimale_reddituale');
            
            // Flag per abilitare calcolo trimestrale (per anni dove è applicabile)
            $table->boolean('calcolo_trimestrale_attivo')->default(true)->after('maggiorazione_oltre_massimale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inps_parameters', function (Blueprint $table) {
            $table->dropColumn([
                'addizionale_ivs_percentuale',
                'massimale_reddituale', 
                'maggiorazione_oltre_massimale',
                'calcolo_trimestrale_attivo'
            ]);
        });
    }
};