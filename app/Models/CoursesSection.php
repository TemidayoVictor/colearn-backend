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
        'videos',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function videos()
    {
        return $this->hasMany(CoursesVideo::class);
    }

    public function resources()
    {
        return $this->hasMany(CoursesResource::class);
    }
}
