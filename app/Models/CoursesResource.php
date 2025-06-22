<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursesResource extends Model
{
    protected $table = "course_resources";

    protected $fillable = [
        'course_id',
        'course_section_id',
        'course_video_id',
        'title',
        'type',
        'file_path',
        'content',
        'is_published',
        'external_url',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function module()
    {
        return $this->belongsTo(CoursesSection::class);
    }

    public function video()
    {
        return $this->belongsTo(CoursesVideo::class);
    }

}
