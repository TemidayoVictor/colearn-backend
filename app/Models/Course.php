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
        'waht_to_learn',
        'thumbnail',
        'is_published',
        'price',
    ];
}
