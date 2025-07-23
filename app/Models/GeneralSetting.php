<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $table = 'general_settings';

    protected $fillable = [
        'course_percentage',
        'consultation_perentage',
        'minimum_withdrawal',
    ];
}
