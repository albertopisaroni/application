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
        Schema::table('f24s', function (Blueprint $table) {
            $table->string('receipt_s3_path', 500)->nullable()->after('s3_url');
            $table->string('receipt_filename', 255)->nullable()->after('receipt_s3_path');
            $table->timestamp('receipt_uploaded_at')->nullable()->after('receipt_filename');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('f24s', function (Blueprint $table) {
            $table->dropColumn(['receipt_s3_path', 'receipt_filename', 'receipt_uploaded_at']);
        });
    }
};
