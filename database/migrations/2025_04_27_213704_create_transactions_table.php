<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stripe_account_id')->constrained()->onDelete('cascade');
            $table->string('stripe_charge_id')->unique();
            $table->integer('amount');
            $table->string('currency', 10)->default('eur');
            $table->string('status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
