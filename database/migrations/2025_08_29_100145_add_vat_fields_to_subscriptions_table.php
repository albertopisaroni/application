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
            $table->decimal('vat_rate', 5, 2)->nullable()->after('final_amount')->comment('Aliquota IVA (es: 22.00)');
            $table->unsignedInteger('vat_amount')->nullable()->after('vat_rate')->comment('Importo IVA in centesimi');
            $table->unsignedInteger('total_with_vat')->nullable()->after('vat_amount')->comment('Totale con IVA in centesimi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['vat_rate', 'vat_amount', 'total_with_vat']);
        });
    }
};
