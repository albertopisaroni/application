<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('unit_amount')->nullable();        // centesimi
            $table->unsignedInteger('subtotal_amount')->nullable();    // centesimi
            $table->unsignedInteger('discount_amount')->nullable();    // centesimi
            $table->unsignedInteger('final_amount')->nullable();       // centesimi
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'quantity',
                'unit_amount',
                'subtotal_amount',
                'discount_amount',
                'final_amount',
            ]);
        });
    }
};