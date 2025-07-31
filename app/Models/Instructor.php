<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    //
    protected $table = "instructors";

    protected $fillable = [
        'user_id',
        'title',
        'professional_headline',
        'phone',
        'profile_photo',
        'bio',
        'country',
        'gender',
        'website',
        'linkedin_url',
        'twitter_url',
        'youtube_url',
        'is_approved',
        'is_active',
        'disciplines',
        'languages',
        'category',
        'consultant_active',
        'intro_video_url',
        'consultant_progress',
        'experience_years',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function experience()
    {
        return $this->hasMany(Experience::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function schools()
    {
        return $this->hasMany(School::class);
    }

    public function certifications()
    {
        return $this->hasMany(Certification::class);
    }

    public function consultant()
    {
        return $this->hasOne(Consultant::class);
    }

    public function totalSales()
{
    return $this->hasManyThrough(
        \App\Models\Cart::class,
        \App\Models\Course::class,
        'instructor_id', // Foreign key on courses table
        'course_id',     // Foreign key on cart table
        'id',            // Local key on instructor
        'id'             // Local key on course
    )->where('cart.status', 'checked_out');
}

}
