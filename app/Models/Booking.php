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
        'user_time',
        'consultant_date',
        'user_end_time',
        'cancel_note',
        'reschedule_time',
        'reschedule_note',
        'reschedule_date',
        'reschedule_date_user',
        'reschedule_time_user',
        'missed_client',
        'missed_consultant',
        'missed_client_note',
        'missed_consultant_note',
    ];

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
