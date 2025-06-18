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
        Schema::table('users', function (Blueprint $table) {
            //
            $table->string('country_phone_code')->nullable();
            $table->string('country_iso')->nullable();
            $table->string('country_iso3')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropColumn('country_phone_code');
            $table->dropColumn('country_iso');
            $table->dropColumn('country_iso3');
        });
    }
};
