<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'lga_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function lga()
    {
        return $this->belongsTo(LGA::class);
    }

    public function pollingUnits()
    {
        return $this->hasMany(PollingUnit::class);
    }

    public function enumerators()
    {
        return $this->hasMany(Enumerator::class, 'ward');
    }

    public function members()
    {
        return $this->hasManyThrough(ExternalMember::class, Enumerator::class, 'ward', 'agentcode');
    }
}
