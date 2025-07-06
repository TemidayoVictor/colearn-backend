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
        Schema::table('bookings', function (Blueprint $table) {
            //
            $table->boolean('missed_client')->nullable();
            $table->boolean('missed_consultant')->nullable();
            $table->string('missed_client_note')->nullable();
            $table->string('missed_consultant_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            //
            $table->dropColumn('missed_client');
            $table->dropColumn('missed_consultant');
            $table->dropColumn('missed_client_note');
            $table->dropColumn('missed_consultant_note');
        });
    }
};
