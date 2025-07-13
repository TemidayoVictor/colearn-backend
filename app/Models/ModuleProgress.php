<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleProgress extends Model
{
    //
    protected $table = "module_progress";

    protected $fillable = [
        'user_id',
        'course_section_id',
        'completed_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function module()
    {
        return $this->belongsTo(CoursesSection::class, 'course_section_id');
    }


}
