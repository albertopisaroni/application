<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            /* -------------------------------------------------------------
             | 1) Rimuovi i campi duplicati del cliente
             |-------------------------------------------------------------
             */
            $table->dropColumn([
                'client_name',
                'client_address',
                'client_email',
                'client_phone',
            ]);

            /* -------------------------------------------------------------
             | 2) Aggiungi i campi di tracking SDI
             |-------------------------------------------------------------
             */
            $table->uuid('sdi_uuid')->nullable()->after('pdf_path');
            $table->string('sdi_status')->default('pending')->after('sdi_uuid');
            $table->string('sdi_error')->nullable()->after('sdi_status');
            $table->text('sdi_error_description')->nullable()->after('sdi_error');
            $table->timestamp('sdi_sent_at')->nullable()->after('sdi_error_description');
            $table->timestamp('sdi_received_at')->nullable()->after('sdi_sent_at');

            $table->index('sdi_status');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            /* -------------------------------------------------------------
             | Ripristina i campi cliente (se proprio necessario)
             |-------------------------------------------------------------
             */
            $table->string('client_name')->nullable();
            $table->string('client_address')->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_phone')->nullable();

            /* -------------------------------------------------------------
             | Rimuovi i campi SDI
             |-------------------------------------------------------------
             */
            $table->dropColumn([
                'sdi_uuid',
                'sdi_status',
                'sdi_error',
                'sdi_error_description',
                'sdi_sent_at',
                'sdi_received_at',
            ]);
        });
    }
};