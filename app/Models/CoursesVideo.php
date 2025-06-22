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
    ];

    public function module()
    {
        return $this->belongsTo(CoursesSection::class);
    }

    public function resources()
    {
        return $this->hasMany(CoursesResource::class);
    }
}
