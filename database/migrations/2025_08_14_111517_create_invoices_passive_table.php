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
        Schema::create('invoices_passive', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            
            // Relazioni (per fatture passive, supplier è il "client", company è chi riceve)
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained('clients')->onDelete('set null'); // Il fornitore
            
            // Dati documento
            $table->string('invoice_number'); // Numero fattura del fornitore
            $table->string('document_type', 10)->default('TD01');
            $table->foreignId('original_invoice_id')->nullable()->constrained('invoices_passive')->onDelete('set null');
            $table->date('issue_date'); // Data emissione
            $table->date('data_accoglienza_file')->nullable(); // Data ricezione
            $table->year('fiscal_year');
            
            // Dati fiscali
            $table->boolean('withholding_tax')->default(false);
            $table->boolean('inps_contribution')->default(false);
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');
            
            // Importi
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat', 10, 2);
            $table->decimal('total', 10, 2);
            $table->decimal('global_discount', 10, 2)->default(0.00);
            
            // Note e contatti
            $table->text('header_notes')->nullable();
            $table->text('footer_notes')->nullable();
            $table->text('contact_info')->nullable();
            
            // Gestione file
            $table->string('pdf_path', 250)->nullable();
            $table->string('pdf_url')->nullable();
            $table->json('xml_payload')->nullable(); // Salva il payload XML completo
            
            // Dati SDI/Ricezione
            $table->uuid('sdi_uuid')->nullable()->unique();
            $table->string('sdi_filename')->nullable(); // Nome file XML ricevuto
            $table->string('sdi_status')->default('received'); // Per passive: received, processed, archived
            $table->string('sdi_error')->nullable();
            $table->text('sdi_error_description')->nullable();
            $table->timestamp('sdi_received_at')->nullable();
            $table->timestamp('sdi_processed_at')->nullable();
            
            // Flags
            $table->boolean('is_processed')->default(false); // Se è stata elaborata contabilmente
            $table->boolean('is_paid')->default(false); // Se è stata pagata
            $table->boolean('imported_from_callback')->default(true); // Per distinguere da import manuali
            
            $table->timestamps();
            
            // Indici
            $table->index(['company_id', 'supplier_id']);
            $table->index(['issue_date']);
            $table->index(['sdi_status']);
            $table->index(['is_processed', 'is_paid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices_passive');
    }
};
