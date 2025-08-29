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
        Schema::table('invoices', function (Blueprint $table) {
            // Campi per tracciare le notifiche customer (RC, NS, MC)
            $table->string('notification_type', 10)->nullable()->after('legal_storage_error')
                  ->comment('Tipo notifica SDI: RC (Ricevuta Consegna), NS (Notifica Scarto), MC (Mancata Consegna)');
            
            $table->string('notification_file_name')->nullable()->after('notification_type')
                  ->comment('Nome file della notifica ricevuta');
            
            $table->string('sdi_identificativo', 50)->nullable()->after('notification_file_name')
                  ->comment('Identificativo SDI dalla ricevuta');
            
            $table->timestamp('sdi_data_ricezione')->nullable()->after('sdi_identificativo')
                  ->comment('Data/ora ricezione dal SDI');
            
            $table->timestamp('sdi_data_consegna')->nullable()->after('sdi_data_ricezione')
                  ->comment('Data/ora consegna al destinatario');
            
            $table->string('sdi_message_id', 50)->nullable()->after('sdi_data_consegna')
                  ->comment('Message ID della notifica SDI');
            
            $table->json('sdi_destinatario')->nullable()->after('sdi_message_id')
                  ->comment('Dati destinatario dalla ricevuta');
            
            // Indici per performance
            $table->index('notification_type', 'invoices_notification_type_index');
            $table->index('sdi_identificativo', 'invoices_sdi_identificativo_index');
            $table->index('sdi_data_consegna', 'invoices_sdi_data_consegna_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Rimuovi indici
            $table->dropIndex('invoices_notification_type_index');
            $table->dropIndex('invoices_sdi_identificativo_index');
            $table->dropIndex('invoices_sdi_data_consegna_index');
            
            // Rimuovi colonne
            $table->dropColumn([
                'notification_type',
                'notification_file_name',
                'sdi_identificativo',
                'sdi_data_ricezione',
                'sdi_data_consegna',
                'sdi_message_id',
                'sdi_destinatario'
            ]);
        });
    }
};