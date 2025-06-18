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
    ];
}
