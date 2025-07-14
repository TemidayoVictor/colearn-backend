<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $table = "enrollments";

    protected $fillable = [
        'user_id',
        'course_id',
        'completed_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'user_id', 'user_id')
            ->where(function ($query) {
                $query->where('course_id', $this->course_id);
            });
    }
}
