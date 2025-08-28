<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifica l'enum payment_status per includere PARTIALLY_PAID
        DB::statement("ALTER TABLE f24s MODIFY COLUMN payment_status ENUM('PENDING', 'PARTIALLY_PAID', 'PAID', 'OVERDUE', 'CANCELLED') DEFAULT 'PENDING'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ripristina l'enum originale
        DB::statement("ALTER TABLE f24s MODIFY COLUMN payment_status ENUM('PENDING', 'PAID', 'OVERDUE', 'CANCELLED') DEFAULT 'PENDING'");
    }
};
