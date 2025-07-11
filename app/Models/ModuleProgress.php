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
}
