<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentMethodIdToInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // aggiungo la colonna
            $table->unsignedBigInteger('payment_method_id')->nullable()->after('numbering_id');

            // definisco la foreign key
            $table->foreign('payment_method_id')
                  ->references('id')
                  ->on('payment_methods')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // elimino prima la foreign key
            $table->dropForeign(['payment_method_id']);

            // poi la colonna
            $table->dropColumn('payment_method_id');
        });
    }
}