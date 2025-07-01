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

}
