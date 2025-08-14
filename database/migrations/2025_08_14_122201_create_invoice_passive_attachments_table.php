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
        Schema::create('invoice_passive_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_passive_id')->constrained('invoices_passive')->onDelete('cascade');
            
            // Dati file
            $table->string('filename'); // Nome originale del file
            $table->string('mime_type'); // application/pdf, image/jpeg, etc.
            $table->string('file_extension', 10); // pdf, jpg, xml, etc.
            $table->bigInteger('file_size'); // Dimensione in bytes
            $table->string('file_hash')->nullable(); // Hash MD5 per integrità
            
            // Percorsi storage
            $table->string('s3_path'); // Percorso su S3
            $table->string('s3_url')->nullable(); // URL pubblico se disponibile
            $table->boolean('is_encrypted')->default(true); // Se il file è criptato
            
            // Metadata
            $table->string('attachment_type')->default('pdf'); // pdf, xml, image, other
            $table->text('description')->nullable(); // Descrizione allegato
            $table->json('metadata')->nullable(); // Metadata aggiuntivi (es: dimensioni immagine)
            
            // Flags
            $table->boolean('is_primary')->default(false); // Se è l'allegato principale (PDF fattura)
            $table->boolean('is_processed')->default(false); // Se è stato elaborato
            
            $table->timestamps();
            
            // Indici
            $table->index(['invoice_passive_id', 'attachment_type'], 'idx_passive_attach_type');
            $table->index(['is_primary', 'attachment_type'], 'idx_primary_attach_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_passive_attachments');
    }
};
