<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    //
    protected $table = "certifications";

    protected $fillable = [
        'instructor_id',
        'name',
        'organization',
        'iss_date',
        'exp_date',
        'credential_url',
        'certificate_file_path',
    ];
}
