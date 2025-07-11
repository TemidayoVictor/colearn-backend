<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursesVideo extends Model
{
    protected $table = "course_videos";

    protected $fillable = [
        'course_section_id',
        'title',
        'video_url',
        'duration',
        'order',
        'overall_order',
        'status',
        'progress',
    ];

    public function module()
    {
        return $this->belongsTo(CoursesSection::class, 'course_section_id');
    }

    public function resources()
    {
        return $this->hasMany(CoursesResource::class, 'course_video_id');
    }
}
