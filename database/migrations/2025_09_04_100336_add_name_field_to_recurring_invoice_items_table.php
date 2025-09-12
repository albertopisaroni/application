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
        Schema::table('recurring_invoice_items', function (Blueprint $table) {
            $table->string('name')->nullable()->after('recurring_invoice_id');
            $table->text('description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurring_invoice_items', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->string('description')->nullable(false)->change();
        });
    }
};