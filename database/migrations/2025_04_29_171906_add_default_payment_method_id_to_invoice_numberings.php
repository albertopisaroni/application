<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('invoice_numberings', function (Blueprint $table) {
            $table->foreignId('default_payment_method_id')
                  ->nullable()
                  ->after('default_footer_notes')
                  ->constrained('payment_methods')
                  ->nullOnDelete();
        });
    }
    
    public function down()
    {
        Schema::table('invoice_numberings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_payment_method_id');
        });
    }
};
