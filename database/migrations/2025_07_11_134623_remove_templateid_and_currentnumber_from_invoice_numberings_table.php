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
            $table->dropColumn(['template_id', 'current_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_numberings', function (Blueprint $table) {
            $table->unsignedInteger('template_id')->nullable()->after('name');
            $table->unsignedInteger('current_number')->default(1)->after('default_payment_method_id');
        });
    }
};
