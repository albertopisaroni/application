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
        // Aggiungi i nuovi tipi di tassa INPS all'enum
        DB::statement("ALTER TABLE taxes MODIFY COLUMN tax_type ENUM(
            'IMPOSTA_SOSTITUTIVA_SALDO',
            'IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO',
            'IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO',
            'IMPOSTA_SOSTITUTIVA_CREDITO',
            'INPS_SALDO',
            'INPS_PRIMO_ACCONTO',
            'INPS_SECONDO_ACCONTO',
            'INPS_TERZO_ACCONTO',
            'INPS_QUARTO_ACCONTO',
            'INPS_CREDITO',
            'INPS_FISSI_SALDO',
            'INPS_FISSI_PRIMO_ACCONTO',
            'INPS_FISSI_SECONDO_ACCONTO',
            'INPS_FISSI_TERZO_ACCONTO',
            'INPS_FISSI_QUARTO_ACCONTO',
            'INPS_PERCENTUALI_SALDO',
            'INPS_PERCENTUALI_PRIMO_ACCONTO',
            'INPS_PERCENTUALI_SECONDO_ACCONTO'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi i nuovi tipi di tassa INPS dall'enum
        DB::statement("ALTER TABLE taxes MODIFY COLUMN tax_type ENUM(
            'IMPOSTA_SOSTITUTIVA_SALDO',
            'IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO',
            'IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO',
            'IMPOSTA_SOSTITUTIVA_CREDITO',
            'INPS_SALDO',
            'INPS_PRIMO_ACCONTO',
            'INPS_SECONDO_ACCONTO',
            'INPS_TERZO_ACCONTO',
            'INPS_QUARTO_ACCONTO',
            'INPS_CREDITO'
        ) NOT NULL");
    }
};
