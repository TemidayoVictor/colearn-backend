<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    //
    protected $fillable = [
        'user_id', 'title', 'slug', 'excerpt', 'body', 'thumbnail', 'is_published'
    ];

    protected static function booted()
    {
        static::creating(function ($blog) {
            $blog->slug = Str::slug($blog->title) . '-' . uniqid();
        });
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
