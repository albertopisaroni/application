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
        Schema::create('f24s', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('company_id');
            $table->string('filename', 255);
            $table->string('s3_path', 500)->nullable();
            $table->string('s3_url', 500)->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->date('due_date')->nullable();
            $table->enum('payment_status', ['PENDING', 'PAID', 'OVERDUE', 'CANCELLED'])->default('PENDING');
            $table->json('sections')->nullable(); // Array delle sezioni presenti (erario, inps, imu, altri)
            $table->json('reference_years')->nullable(); // Array degli anni di riferimento
            $table->text('notes')->nullable();
            $table->timestamp('imported_at')->useCurrent();
            $table->timestamps();
            
            // Indici
            $table->index(['company_id', 'payment_status'], 'idx_company_status');
            $table->index('due_date', 'idx_due_date');
            $table->index('imported_at', 'idx_imported_at');
            
            // Foreign key
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('f24s');
    }
};
