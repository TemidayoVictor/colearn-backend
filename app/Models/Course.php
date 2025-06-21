<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = "courses";

    protected $fillable = [
        'instructor_id',
        'title',
        'description',
        'who_can_enroll',
        'thumbnail',
        'is_published',
        'price',
        'is_free',
        'videos',
    ];
}
