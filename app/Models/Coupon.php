<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table = "coupons";

    protected $fillable = [
        'code', 'type', 'value', 'instructor_id', 'course_id',
        'usage_limit', 'used_count', 'expires_at', 'status'
    ];

    protected $dates = ['expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function isValid()
    {
        return (!$this->expires_at || $this->expires_at->isFuture()) &&
        ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }
}
