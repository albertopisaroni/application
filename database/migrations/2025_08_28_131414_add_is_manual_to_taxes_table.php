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
        Schema::table('taxes', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('notes')->comment('Indica se la tassa è stata caricata manualmente (true) o calcolata automaticamente (false)');
            
            // Indice per ottimizzare le query
            $table->index('is_manual', 'idx_is_manual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropIndex('idx_is_manual');
            $table->dropColumn('is_manual');
        });
    }
};
