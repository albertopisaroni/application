<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSdiAttemptToInvoicesTable extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedInteger('sdi_attempt')->default(1)->after('sdi_received_at')
                  ->comment('Numero di tentativi di invio al SDI');
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('sdi_attempt');
        });
    }
}