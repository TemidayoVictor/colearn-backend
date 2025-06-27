<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    //
    protected $table = "certifications";

    protected $fillable = [
        'instructor_id',
        'certification_name',
        'issuing_organization',
        'issue_date',
        'expiry_date',
        'credential_url',
        'certificate_file_path',
    ];
}
