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
            $table->uuid('f24_id')->nullable()->after('company_id');
            $table->enum('section_type', ['erario', 'inps', 'imu', 'altri'])->nullable()->after('f24_id');
            
            // Indici
            $table->index('f24_id', 'idx_f24_id');
            $table->index('section_type', 'idx_section_type');
            
            // Foreign key
            $table->foreign('f24_id')->references('id')->on('f24s')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropForeign(['f24_id']);
            $table->dropIndex('idx_f24_id');
            $table->dropIndex('idx_section_type');
            $table->dropColumn(['f24_id', 'section_type']);
        });
    }
};
