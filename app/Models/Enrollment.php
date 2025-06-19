<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $table = "enrollments";

    protected $fillable = [
        'user_id',
        'course_id',
    ];
}
