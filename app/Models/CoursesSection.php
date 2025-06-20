<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursesSection extends Model
{
    protected $table = "course_sections";

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'order',
    ];
}
