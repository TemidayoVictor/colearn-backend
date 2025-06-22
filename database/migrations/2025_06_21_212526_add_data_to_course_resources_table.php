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
        Schema::table('course_resources', function (Blueprint $table) {
            //
            $table->integer('course_section_id')->nullable();
            $table->integer('course_video_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_resources', function (Blueprint $table) {
            //
            $table->dropColumn('course_section_id');
            $table->dropColumn('course_video_id');
        });
    }
};
