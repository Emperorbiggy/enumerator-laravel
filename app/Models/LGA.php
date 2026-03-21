<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LGA extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function wards()
    {
        return $this->hasMany(Ward::class);
    }

    public function pollingUnits()
    {
        return $this->hasManyThrough(PollingUnit::class, Ward::class);
    }

    public function enumerators()
    {
        return $this->hasMany(Enumerator::class, 'lga');
    }

    public function members()
    {
        return $this->hasManyThrough(ExternalMember::class, Enumerator::class, 'lga', 'agentcode');
    }
}
