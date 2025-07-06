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
        Schema::create('meta_domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('logo_path')->nullable(); // es: logos/meta/salesspa.com.svg
            $table->string('source_url')->nullable(); // da dove è stato preso (logo.dev / brandfetch)
            $table->boolean('is_custom')->default(false); // se caricato manualmente da admin
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
