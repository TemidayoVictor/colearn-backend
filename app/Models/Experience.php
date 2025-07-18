<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    //
    protected $table = "experiences";

    protected $fillable = [
        'instructor_id',
        'title',
        'organization',
        'description',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }
}
