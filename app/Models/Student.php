<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    //
    protected $fillable = [
        'user_id',
        'phone',
        'profile_photo',
        'bio',
        'country',
        'gender',
        'is_active',
        'languages',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
