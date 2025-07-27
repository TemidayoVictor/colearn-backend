<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = "courses";

    protected $fillable = [
        'instructor_id',
        'title',
        'description',
        'who_can_enroll',
        'thumbnail',
        'is_published',
        'price',
        'is_free',
        'videos_count',
        'total_duration',
        'level',
        'summary',
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function modules()
    {
        return $this->hasMany(CoursesSection::class);
    }

    public function resources()
    {
        return $this->hasMany(CoursesResource::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'course_category', 'course_id', 'category_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
