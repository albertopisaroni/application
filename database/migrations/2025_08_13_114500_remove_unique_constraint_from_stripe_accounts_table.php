<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stripe_accounts', function (Blueprint $table) {
            // Remove unique constraint from stripe_user_id
            $table->dropUnique(['stripe_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('stripe_accounts', function (Blueprint $table) {
            // Re-add unique constraint if rollback is needed
            $table->unique('stripe_user_id');
        });
    }
};
