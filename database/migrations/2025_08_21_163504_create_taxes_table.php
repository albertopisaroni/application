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
        Schema::create('taxes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('company_id');
            $table->integer('tax_year'); // Anno di competenza dei redditi
            $table->integer('payment_year'); // Anno in cui si effettua il pagamento
            $table->enum('tax_type', [
                'IMPOSTA_SOSTITUTIVA_SALDO',
                'IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO',
                'IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO',
                'IMPOSTA_SOSTITUTIVA_CREDITO',
                'INPS_SALDO',
                'INPS_PRIMO_ACCONTO',
                'INPS_SECONDO_ACCONTO',
                'INPS_CREDITO'
            ]);
            $table->string('description', 255)->nullable();
            $table->string('tax_code', 10)->nullable(); // Codice tributo F24 (es: 4001, 4033, AP, IVS)
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->enum('payment_status', ['PENDING', 'PAID', 'OVERDUE', 'CANCELLED', 'CREDIT'])->default('PENDING');
            $table->string('f24_url', 500)->nullable();
            $table->timestamp('f24_generated_at')->nullable();
            $table->date('paid_date')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indici
            $table->index(['company_id', 'tax_year', 'payment_year'], 'idx_company_year');
            $table->index('due_date', 'idx_due_date');
            $table->index('payment_status', 'idx_payment_status');
            $table->unique(['company_id', 'tax_year', 'tax_type'], 'unique_tax_record');
            
            // Foreign key
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};