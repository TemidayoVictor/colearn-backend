<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstructorReview extends Model
{
    protected $table = "instructor_reviews";

    protected $fillable = [
        'user_id',
        'instructor_id',
        'title',
        'rating',
        'review',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }
}
