<?php

// database/migrations/xxxx_xx_xx_xxxxxx_update_contacts_nullable_name_surname.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('surname')->nullable()->change();
        });

        DB::table('contacts')->whereNull('email')->delete(); // solo se sei sicuro!

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change(); // forza required
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('surname')->nullable(false)->change();
            $table->string('email')->nullable()->change(); // rollback
        });
    }
};