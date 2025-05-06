<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('coefficiente', 5, 2)->default(78.00)->after('long_description');
            $table->boolean('startup')->default(false)->after('coefficiente');
            $table->decimal('fatturato_annuale', 15, 2)->nullable()->after('startup');
            $table->boolean('gestione_separata')->default(true)->after('fatturato_annuale');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'coefficiente',
                'startup',
                'fatturato_annuale',
                'gestione_separata',
            ]);
        });
    }
};