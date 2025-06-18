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
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instructor_id');
            $table->string('title'); // e.g. "Senior Web Developer"
            $table->string('organization')->nullable(); // e.g. "Udemy"
            $table->text('description')->nullable(); // Experience details
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable(); // Nullable for "currently working"
            $table->boolean('is_current')->default(false); // True if still working there
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experiences');
    }
};
