<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';

    protected $fillable = [
        'user_id',
        'consultant_id',
        'date',
        'start_time',
        'end_time',
        'duration',
        'amount',
        'status',
        'note',
        'date_string',
        'consultant_note',
        'payment_status',
        'channel',
        'booking_type',
        'booking_link',
    ];
}
