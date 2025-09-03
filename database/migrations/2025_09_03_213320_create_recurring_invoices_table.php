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
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('numbering_id')->constrained('invoice_numberings')->onDelete('cascade');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
            
            // Template data for the recurring invoice
            $table->string('template_name')->nullable();
            $table->text('header_notes')->nullable();
            $table->text('footer_notes')->nullable();
            $table->text('contact_info')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat', 10, 2);
            $table->decimal('total', 10, 2);
            $table->decimal('global_discount', 10, 2)->default(0);
            $table->boolean('withholding_tax')->default(false);
            $table->boolean('inps_contribution')->default(false);
            
            // Recurrence settings
            $table->enum('recurrence_type', ['days', 'weeks', 'months', 'years']);
            $table->integer('recurrence_interval'); // every X days/weeks/months/years
            $table->date('start_date'); // when to start generating invoices
            $table->date('end_date')->nullable(); // when to stop (null = indefinite)
            $table->date('next_invoice_date'); // calculated next invoice date
            
            // Status and tracking
            $table->boolean('is_active')->default(true);
            $table->integer('invoices_generated')->default(0);
            $table->integer('max_invoices')->nullable(); // max number of invoices to generate (null = unlimited)
            $table->timestamp('last_generated_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['next_invoice_date', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
