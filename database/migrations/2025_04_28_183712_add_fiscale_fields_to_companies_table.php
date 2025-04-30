<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // true = forfettario, false = ordinario
            $table->boolean('forfettario')
                  ->default(true)
                  ->after('piva');

            // Regime fiscale (RF19 default per forfettari)
            $table->string('regime_fiscale', 4)
                  ->default('RF19')
                  ->after('forfettario');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['forfettario', 'regime_fiscale']);
        });
    }
};