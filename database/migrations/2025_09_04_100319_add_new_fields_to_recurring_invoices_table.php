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
        Schema::table('recurring_invoices', function (Blueprint $table) {
            // Check and add Stripe integration fields if they don't exist
            if (!Schema::hasColumn('recurring_invoices', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')->nullable()->after('payment_method_id');
            }
            if (!Schema::hasColumn('recurring_invoices', 'trigger_on_payment')) {
                $table->boolean('trigger_on_payment')->default(false)->after('payment_method_id');
            }
            
            // Document type and DDT fields
            if (!Schema::hasColumn('recurring_invoices', 'document_type')) {
                $table->string('document_type', 10)->default('TD01')->after('contact_info');
            }
            if (!Schema::hasColumn('recurring_invoices', 'ddt_number')) {
                $table->string('ddt_number')->nullable()->after('contact_info');
            }
            if (!Schema::hasColumn('recurring_invoices', 'ddt_date')) {
                $table->date('ddt_date')->nullable()->after('contact_info');
            }
            
            // Payment terms fields
            if (!Schema::hasColumn('recurring_invoices', 'split_payments')) {
                $table->boolean('split_payments')->default(false)->after('inps_contribution');
            }
            if (!Schema::hasColumn('recurring_invoices', 'due_option')) {
                $table->string('due_option', 20)->default('on_receipt')->after('inps_contribution');
            }
            if (!Schema::hasColumn('recurring_invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('inps_contribution');
            }
            if (!Schema::hasColumn('recurring_invoices', 'payments')) {
                $table->json('payments')->nullable()->after('inps_contribution');
            }
            
            // Recurrence mode field
            if (!Schema::hasColumn('recurring_invoices', 'recurrence_mode')) {
                $table->enum('recurrence_mode', ['manual', 'stripe'])->default('manual')->after('max_invoices');
            }
        });
        
        // Make recurrence fields nullable in a separate statement
        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->enum('recurrence_type', ['days', 'weeks', 'months', 'years'])->nullable()->change();
            $table->integer('recurrence_interval')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_subscription_id',
                'trigger_on_payment',
                'document_type',
                'ddt_number',
                'ddt_date',
                'split_payments',
                'due_option',
                'due_date',
                'payments',
                'recurrence_mode'
            ]);
            
            // Revert recurrence fields to non-nullable
            $table->enum('recurrence_type', ['days', 'weeks', 'months', 'years'])->nullable(false)->change();
            $table->integer('recurrence_interval')->nullable(false)->change();
        });
    }
};