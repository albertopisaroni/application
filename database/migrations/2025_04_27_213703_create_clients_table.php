<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('stripe_account_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('stripe_customer_id')->nullable()->unique();
            $table->string('origin')->default('internal'); // internal / stripe / altro in futuro
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['stripe_account_id', 'stripe_customer_id', 'origin']);
        });
    }
};