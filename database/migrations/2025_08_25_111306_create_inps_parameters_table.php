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
        Schema::create('inps_parameters', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique(); // Anno di riferimento
            
            // MINIMALI
            $table->decimal('minimale_commercianti_artigiani', 10, 2); // es. 18415.00
            
            // ALIQUOTE COMMERCIANTI
            $table->decimal('aliquota_commercianti', 5, 4); // es. 0.2448 (24.48%)
            $table->decimal('aliquota_commercianti_ridotta', 5, 4); // es. 0.1591 (15.91%)
            $table->decimal('aliquota_commercianti_maggiorata', 5, 4)->nullable(); // es. 0.2548 (25.48%)
            $table->decimal('aliquota_commercianti_maggiorata_ridotta', 5, 4)->nullable(); // es. 0.1656 (16.56%)
            
            // ALIQUOTE ARTIGIANI
            $table->decimal('aliquota_artigiani', 5, 4); // es. 0.24 (24%)
            $table->decimal('aliquota_artigiani_ridotta', 5, 4); // es. 0.156 (15.6%)
            $table->decimal('aliquota_artigiani_maggiorata', 5, 4)->nullable(); // es. 0.25 (25%)
            $table->decimal('aliquota_artigiani_maggiorata_ridotta', 5, 4)->nullable(); // es. 0.1625 (16.25%)
            
            // ALIQUOTE GESTIONE SEPARATA
            $table->decimal('aliquota_gestione_separata', 5, 4); // es. 0.2607 (26.07%)
            $table->decimal('aliquota_gestione_separata_ridotta', 5, 4); // es. 0.1695 (16.95%)
            
            // CONTRIBUTI FISSI
            $table->decimal('contributo_fisso_commercianti', 10, 2); // es. 4549.70
            $table->decimal('contributo_fisso_commercianti_ridotto', 10, 2); // es. 2957.31
            $table->decimal('contributo_fisso_artigiani', 10, 2); // es. 4460.64
            $table->decimal('contributo_fisso_artigiani_ridotto', 10, 2); // es. 2899.42
            
            // CONTRIBUTO MATERNITÀ
            $table->decimal('contributo_maternita_annuo', 10, 2); // es. 61.49 o 89.28
            
            // MASSIMALI E SOGLIE
            $table->decimal('massimale_commercianti_artigiani', 10, 2)->default(91187); // es. 91187
            $table->decimal('massimale_gestione_separata', 10, 2)->default(125807); // es. 125807
            $table->decimal('soglia_aliquota_maggiorata', 10, 2)->nullable(); // es. 55448
            
            // DIRITTO ANNUALE CCIAA
            $table->decimal('diritto_annuale_cciaa', 10, 2)->default(53.21); // es. 53.21
            
            $table->timestamps();
            
            // Indici
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inps_parameters');
    }
};