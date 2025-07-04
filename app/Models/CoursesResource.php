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
        'category',
        'file_path',
        'content',
        'external_url',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function module()
    {
        return $this->belongsTo(CoursesSection::class, 'course_section_id');
    }

    public function video()
    {
        return $this->belongsTo(CoursesVideo::class,'course_video_id');
    }

}
