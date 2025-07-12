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
        'videos_count',
        'status',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function videos()
    {
        return $this->hasMany(CoursesVideo::class, 'course_section_id');
    }

    public function resources()
    {
        return $this->hasMany(CoursesResource::class, 'course_section_id');
    }
}
