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
        Schema::create('invoice_passive_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_passive_id')->constrained('invoices_passive')->onDelete('cascade');
            
            // Dati riga fattura
            $table->integer('line_number')->default(1); // Numero linea
            $table->string('name'); // Nome prodotto/servizio
            $table->text('description')->nullable(); // Descrizione
            $table->decimal('quantity', 10, 5)->default(1.00000);
            $table->string('unit_of_measure', 10)->nullable(); // Es: PZ, KG, H, etc.
            $table->decimal('unit_price', 10, 2); // Prezzo unitario
            $table->decimal('line_total', 10, 2); // Totale riga (quantity * unit_price)
            $table->decimal('vat_rate', 5, 2); // Aliquota IVA (es: 22.00)
            $table->decimal('vat_amount', 10, 2); // Importo IVA
            
            // Campi aggiuntivi per fatturazione elettronica
            $table->string('product_code')->nullable(); // Codice articolo
            $table->date('period_start')->nullable(); // Data inizio periodo
            $table->date('period_end')->nullable(); // Data fine periodo
            $table->json('discount_data')->nullable(); // Eventuali sconti/maggiorazioni
            $table->json('additional_data')->nullable(); // Altri dati gestionali
            
            $table->timestamps();
            
            // Indici
            $table->index(['invoice_passive_id', 'line_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_passive_items');
    }
};
