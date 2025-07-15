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
        Schema::table('wallets', function (Blueprint $table) {
            // First drop the foreign key
            $table->dropForeign(['wallet_id']);

            // Then drop the column
            $table->dropColumn('wallet_id');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
        });
    }
};
