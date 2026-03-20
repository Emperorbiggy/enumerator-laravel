<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Enumerator;

class DataSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'phone',
        'plan_code',
        'plan_name',
        'network',
        'plan_type',
        'amount',
        'balance_before',
        'balance_after',
        'response_message',
        'status',
        'full_response',
        'enumerator_id',
        'admin_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'full_response' => 'array',
    ];

    public function enumerator()
    {
        return $this->belongsTo(Enumerator::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
