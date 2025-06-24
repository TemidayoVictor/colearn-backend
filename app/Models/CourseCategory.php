<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
    //
    protected $table = 'course_category';

    protected $fillable = [
        'course_id',
        'category_id',
    ];

    public function courses()
{
    return $this->belongsToMany(Course::class, 'course_category', 'category_id', 'course_id');
}
}
