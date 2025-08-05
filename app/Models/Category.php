<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    //
    protected $table = "categories";

    protected $fillable = [
        'name',
        'slug',
        'description',
        'thumbnail',
    ];

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_category', 'category_id', 'course_id');
    }
}
