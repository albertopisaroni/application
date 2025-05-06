<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoice_numberings', function (Blueprint $table) {
            $table->longText('logo_base64_square')->nullable()->after('logo_base64');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_numberings', function (Blueprint $table) {
            $table->dropColumn('logo_base64_square');
        });
    }
};