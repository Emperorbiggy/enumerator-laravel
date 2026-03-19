<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalMember extends Model
{
    use HasFactory;

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'external_mysql';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'members';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
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
        'qr_code_path',
        'registration_date',
        'agentcode',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'registration_date' => 'datetime',
    ];

    /**
     * Get the full name attribute.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
