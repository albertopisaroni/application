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
        Schema::table('invoices', function (Blueprint $table) {
            // Aggiungi il campo per lo stato di conservazione sostitutiva
            $table->string('legal_storage_status', 50)->nullable()->after('sdi_error_description')
                  ->comment('Stato conservazione sostitutiva: null, pending, stored, failed');
            
            // Aggiungi campi aggiuntivi per tracciare i dettagli della conservazione
            $table->string('legal_storage_uuid')->nullable()->after('legal_storage_status')
                  ->comment('UUID della ricevuta di conservazione');
            
            $table->timestamp('legal_storage_completed_at')->nullable()->after('legal_storage_uuid')
                  ->comment('Data completamento conservazione');
            
            $table->text('legal_storage_error')->nullable()->after('legal_storage_completed_at')
                  ->comment('Messaggio di errore conservazione se presente');
            
            // Indice per performance su queries di conservazione
            $table->index('legal_storage_status', 'invoices_legal_storage_status_index');
            $table->index('legal_storage_completed_at', 'invoices_legal_storage_completed_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Rimuovi gli indici prima di eliminare le colonne
            $table->dropIndex('invoices_legal_storage_status_index');
            $table->dropIndex('invoices_legal_storage_completed_at_index');
            
            // Rimuovi le colonne
            $table->dropColumn([
                'legal_storage_status',
                'legal_storage_uuid', 
                'legal_storage_completed_at',
                'legal_storage_error'
            ]);
        });
    }
};