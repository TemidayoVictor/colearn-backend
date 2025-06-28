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
        Schema::create('consultants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained()->onDelete('cascade'); // link to instructors
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->json('available_days')->nullable(); // e.g. ["monday", "tuesday"]
            $table->time('available_time_start')->nullable(); // e.g. 09:00
            $table->time('available_time_end')->nullable(); // e.g. 17:00
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultants');
    }
};
