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
        Schema::table('certifications', function (Blueprint $table) {
            //
            $table->renameColumn('issue_date', 'iss_date');
            $table->renameColumn('expiry_date', 'exp_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certifications', function (Blueprint $table) {
            //
            $table->renameColumn('issue_date', 'iss_date');
            $table->renameColumn('expiry_date', 'exp_date');
        });
    }
};
