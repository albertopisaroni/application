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
        Schema::table('invoices_passive', function (Blueprint $table) {
            // Campi riassuntivi per allegati
            $table->boolean('has_attachments')->default(false)->after('imported_from_callback');
            $table->integer('attachments_count')->default(0)->after('has_attachments');
            $table->string('primary_attachment_path')->nullable()->after('attachments_count'); // Path principale (PDF)
            $table->string('primary_attachment_filename')->nullable()->after('primary_attachment_path'); // Nome file principale
            $table->json('attachments_summary')->nullable()->after('primary_attachment_filename'); // Summary di tutti gli allegati
            
            // Indici per performance
            $table->index(['has_attachments']);
            $table->index(['attachments_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices_passive', function (Blueprint $table) {
            $table->dropIndex(['has_attachments']);
            $table->dropIndex(['attachments_count']);
            $table->dropColumn([
                'has_attachments',
                'attachments_count', 
                'primary_attachment_path',
                'primary_attachment_filename',
                'attachments_summary'
            ]);
        });
    }
};
