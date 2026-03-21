<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollingUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'ward_id',
        'registered_voters',
        'description'
    ];

    protected $casts = [
        'registered_voters' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function lga()
    {
        return $this->hasOneThrough(LGA::class, Ward::class);
    }

    public function enumerators()
    {
        return $this->hasMany(Enumerator::class, 'polling_unit');
    }

    public function members()
    {
        return $this->hasManyThrough(ExternalMember::class, Enumerator::class, 'polling_unit', 'agentcode');
    }
}
