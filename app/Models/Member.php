<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'nin',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'phone_number',
        'email',
        'state',
        'lga',
        'ward',
        'polling_unit',
        'residential_address',
        'photo_path',
        'membership_number',
        'registration_date',
        'agentcode',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'registration_date' => 'date',
    ];
}
