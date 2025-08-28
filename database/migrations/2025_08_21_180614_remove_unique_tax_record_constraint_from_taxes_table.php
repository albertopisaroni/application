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
        Schema::table('taxes', function (Blueprint $table) {
            // Rimuovi il vincolo di unicità
            $table->dropUnique('unique_tax_record');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            // Ricrea il vincolo di unicità
            $table->unique(['company_id', 'tax_year', 'tax_type'], 'unique_tax_record');
        });
    }
};
