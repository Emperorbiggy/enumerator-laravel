<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enumerator extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'full_name',
        'email',
        'whatsapp',
        'state',
        'lga',
        'ward',
        'polling_unit',
        'browsing_network',
        'browsing_number',
        'bank_name',
        'account_name',
        'account_number',
        'group_name',
        'coordinator_phone',
        'registered_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the external members registered by this enumerator
     */
    public function externalMembers()
    {
        return $this->hasMany(ExternalMember::class, 'agentcode', 'code');
    }
}
