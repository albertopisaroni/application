<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stripe_accounts', function (Blueprint $table) {
            $table->foreignId('invoice_numbering_id')
                  ->nullable()
                  ->constrained('invoice_numberings')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stripe_accounts', function (Blueprint $table) {
            $table->dropForeign(['invoice_numbering_id']);
            $table->dropColumn('invoice_numbering_id');
        });
    }
};