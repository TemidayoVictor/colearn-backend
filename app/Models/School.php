<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    //
    protected $table = "schools";

    protected $fillable = [
        'instructor_id',
        'institution_name',
        'degree', // e.g. BSc, MSc, PhD
        'field_of_study',
        'start_year',
        'end_year',
        'description',
    ];
}
