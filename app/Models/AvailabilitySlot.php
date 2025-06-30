<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailabilitySlot extends Model
{
    //
    protected $table = 'availability_slots';

    protected $fillable = [
        'consultant_id',
        'day',
        'start_time',
        'end_time',
        'enabled',
    ];

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }
}
