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
        Schema::table('companies', function (Blueprint $table) {
            // regime_fiscale già esistente, aggiungo solo total_revenue se non esiste
            if (!Schema::hasColumn('companies', 'total_revenue')) {
                $table->decimal('total_revenue', 15, 2)->nullable()->after('agevolazione_inps')->comment('Fatturato totale anno precedente');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'total_revenue')) {
                $table->dropColumn('total_revenue');
            }
        });
    }
};