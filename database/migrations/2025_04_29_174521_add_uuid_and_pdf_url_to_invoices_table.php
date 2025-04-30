<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique();
            $table->string('pdf_url')->after('pdf_path')->nullable();
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'pdf_url']);
        });
    }
};