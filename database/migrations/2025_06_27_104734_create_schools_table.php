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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instructor_id');
            $table->string('institution_name');
            $table->string('degree'); // e.g. BSc, MSc, PhD
            $table->string('field_of_study')->nullable();
            $table->string('start_year')->nullable();
            $table->string('end_year')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('instructor_id')->references('id')->on('instructors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
