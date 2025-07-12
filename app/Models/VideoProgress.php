<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoProgress extends Model
{
    //
    protected $table = "video_progress";

    protected $fillable = [
        'user_id',
        'course_video_id',
        'course_id',
        'watched_percentage',
        'completed_at',
    ];

    public function video()
    {
        return $this->belongsTo(CoursesVideo::class, 'course_video_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

}
