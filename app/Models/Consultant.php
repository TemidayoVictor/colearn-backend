<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultant extends Model
{
    //
    protected $table = 'consultants';

    protected $fillable = [
        'instructor_id',
        'hourly_rate',
        'available_days',
        'available_time_start',
        'available_time_end',
        'type',
        'status',
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function slots()
    {
        return $this->hasMany(AvailabilitySlot::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
