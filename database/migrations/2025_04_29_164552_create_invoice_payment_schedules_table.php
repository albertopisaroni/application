<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('invoice_payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')
                ->constrained()
                ->onDelete('cascade');
            $table->date('due_date');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['percent','amount'])->default('amount');
            $table->unsignedTinyInteger('percent')->nullable(); // se ti serve
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_payment_schedules');
    }
};
