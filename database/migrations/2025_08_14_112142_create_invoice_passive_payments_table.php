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
        Schema::create('invoice_passive_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_passive_id')->constrained('invoices_passive')->onDelete('cascade');
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');
            
            // Dati pagamento
            $table->decimal('amount', 10, 2); // Importo pagato
            $table->date('payment_date'); // Data pagamento
            $table->date('due_date')->nullable(); // Data scadenza originale
            $table->string('reference')->nullable(); // Riferimento pagamento (es: bonifico, assegno, etc.)
            $table->text('notes')->nullable(); // Note sul pagamento
            
            // Dati bancari/pagamento
            $table->string('iban')->nullable(); // IBAN del fornitore
            $table->string('bank_name')->nullable(); // Nome banca
            $table->string('transaction_id')->nullable(); // ID transazione
            
            // Stato
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->boolean('is_verified')->default(false); // Se il pagamento è stato verificato
            
            $table->timestamps();
            
            // Indici
            $table->index(['invoice_passive_id', 'status']);
            $table->index(['payment_date']);
            $table->index(['due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_passive_payments');
    }
};
