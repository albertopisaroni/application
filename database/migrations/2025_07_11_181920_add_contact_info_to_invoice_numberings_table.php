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
        Schema::table('invoice_numberings', function (Blueprint $table) {
            $table->text('contact_info')->nullable()->after('default_footer_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_numberings', function (Blueprint $table) {
            $table->dropColumn('contact_info');
        });
    }
};
