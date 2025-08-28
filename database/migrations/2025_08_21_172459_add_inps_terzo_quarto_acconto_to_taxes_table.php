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
        // Aggiorna l'enum per includere le nuove rate INPS
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi le rate aggiuntive
        DB::statement("ALTER TABLE taxes MODIFY COLUMN tax_type ENUM(
            'IMPOSTA_SOSTITUTIVA_SALDO',
            'IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO',
            'IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO',
            'IMPOSTA_SOSTITUTIVA_CREDITO',
            'INPS_SALDO',
            'INPS_PRIMO_ACCONTO',
            'INPS_SECONDO_ACCONTO',
            'INPS_CREDITO'
        ) NOT NULL");
    }
};