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
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('data_accoglienza_file')->nullable()->after('issue_date');
            $table->string('sdi_id_invio')->nullable()->after('sdi_uuid');
            $table->boolean('imported_from_ae')->default(false)->after('sdi_attempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'data_accoglienza_file',
                'sdi_id_invio',
                'imported_from_ae'
            ]);
        });
    }
};
