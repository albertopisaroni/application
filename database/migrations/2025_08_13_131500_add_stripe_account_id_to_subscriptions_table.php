<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('stripe_account_id')
                  ->nullable()
                  ->after('company_id')
                  ->constrained('stripe_accounts')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['stripe_account_id']);
            $table->dropColumn('stripe_account_id');
        });
    }
};
