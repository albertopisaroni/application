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
        // Aggiungi commento alla colonna sdi_status per documentare i possibili valori
        DB::statement("
            ALTER TABLE invoices 
            MODIFY COLUMN sdi_status VARCHAR(255) NOT NULL DEFAULT 'pending' 
            COMMENT 'Stato SDI della fattura. Valori possibili:
            - pending: In attesa di invio
            - sent: Inviata al SDI
            - received: Ricevuta dal SDI (accettata)
            - delivered: Consegnata al destinatario (RC)
            - rejected: Rifiutata/Scartata dal SDI (NS)
            - delivery_failed: Mancata consegna (MC)
            - error: Errore generico
            - processed: Processata
            - unknown: Stato sconosciuto'
        ");

        // Migliora l'indice esistente su sdi_status se necessario
        Schema::table('invoices', function (Blueprint $table) {
            // Verifica se l'indice esiste già, altrimenti lo crea
            if (!$this->indexExists('invoices', 'invoices_sdi_status_index')) {
                $table->index('sdi_status', 'invoices_sdi_status_index');
            }
            
            // Aggiungi un indice composto per query comuni
            if (!$this->indexExists('invoices', 'invoices_sdi_status_received_at_index')) {
                $table->index(['sdi_status', 'sdi_received_at'], 'invoices_sdi_status_received_at_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi il commento dalla colonna
        DB::statement("
            ALTER TABLE invoices 
            MODIFY COLUMN sdi_status VARCHAR(255) NOT NULL DEFAULT 'pending' 
            COMMENT ''
        ");

        Schema::table('invoices', function (Blueprint $table) {
            // Rimuovi gli indici aggiunti
            if ($this->indexExists('invoices', 'invoices_sdi_status_index')) {
                $table->dropIndex('invoices_sdi_status_index');
            }
            
            if ($this->indexExists('invoices', 'invoices_sdi_status_received_at_index')) {
                $table->dropIndex('invoices_sdi_status_received_at_index');
            }
        });
    }

    /**
     * Verifica se un indice esiste
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};