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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date'); // Booking date
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration'); // e.g., 30, 60
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // e.g., pending, confirmed, cancelled
            $table->string('date_string')->nullable(); // Optional string representation of the date
            $table->text('note')->nullable();
            $table->text('consultant_note')->nullable();
            $table->string('payment_status')->default('unpaid'); // e.g., unpaid, paid, refunded
            $table->string('channel')->nullable(); // e.g., Zoom, Google Meet
            $table->string('booking_type')->default('online'); // e.g., online, in-person
            $table->string('booking_link')->nullable(); // e.g., website, app, referral
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
