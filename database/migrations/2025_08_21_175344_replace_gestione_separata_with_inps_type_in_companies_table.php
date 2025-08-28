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
        Schema::table('companies', function (Blueprint $table) {
            // Aggiungi il nuovo campo
            $table->enum('inps_type', ['GESTIONE_SEPARATA', 'ARTIGIANI', 'COMMERCIANTI'])->nullable()->after('agevolazione_inps');
        });
        
        // Migra i dati esistenti
        DB::statement("UPDATE companies SET inps_type = CASE 
            WHEN gestione_separata = 1 THEN 'GESTIONE_SEPARATA'
            ELSE 'COMMERCIANTI'
        END");
        
        // Rimuovi il vecchio campo
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('gestione_separata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Ricrea il vecchio campo
            $table->boolean('gestione_separata')->default(false)->after('agevolazione_inps');
        });
        
        // Migra i dati indietro
        DB::statement("UPDATE companies SET gestione_separata = CASE 
            WHEN inps_type = 'GESTIONE_SEPARATA' THEN 1
            ELSE 0
        END");
        
        // Rimuovi il nuovo campo
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('inps_type');
        });
    }
};
