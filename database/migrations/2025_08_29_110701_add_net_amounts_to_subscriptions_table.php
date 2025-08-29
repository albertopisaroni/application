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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('final_amount_net')->nullable()->after('total_with_vat')->comment('Importo finale netto dopo commissioni Stripe (centesimi)');
            $table->unsignedInteger('total_with_vat_net')->nullable()->after('final_amount_net')->comment('Totale con IVA netto dopo commissioni Stripe (centesimi)');
            $table->unsignedInteger('stripe_fees')->nullable()->after('total_with_vat_net')->comment('Commissioni Stripe totali (centesimi)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['final_amount_net', 'total_with_vat_net', 'stripe_fees']);
        });
    }
};