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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('codice_fiscale', 16)->nullable();
            $table->string('rea_ufficio', 2)->nullable();
            $table->string('rea_numero', 10)->nullable();
            $table->string('rea_stato_liquidazione', 2)->nullable();
            // la email e la pec probabilmente ci sono già; altrimenti:
            // $table->string('email')->nullable();
            // $table->string('pec')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'codice_fiscale',
                'rea_ufficio',
                'rea_numero',
                'rea_stato_liquidazione',
                // 'email','pec'
            ]);
        });
    }
};
