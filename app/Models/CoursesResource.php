<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursesResource extends Model
{
    protected $table = "course_resources";

    protected $fillable = [
        'course_id',
        'title',
        'type',
        'file_path',
        'content',
        'is_published',
        'external_url',
    ];
}
