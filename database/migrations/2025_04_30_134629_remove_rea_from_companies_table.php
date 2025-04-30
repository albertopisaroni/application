<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveReaFromCompaniesTable extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('rea');
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('rea')->nullable();
        });
    }
}