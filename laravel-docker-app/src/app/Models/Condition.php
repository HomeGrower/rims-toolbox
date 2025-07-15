<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Condition extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'hotel_chain_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function hotelChain(): BelongsTo
    {
        return $this->belongsTo(HotelChain::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGeneral($query)
    {
        return $query->where('type', 'general');
    }

    public function scopeForChain($query, $chainId)
    {
        return $query->where(function ($q) use ($chainId) {
            $q->where('type', 'general')
              ->orWhere(function ($q2) use ($chainId) {
                  $q2->where('type', 'chain_specific')
                     ->where('hotel_chain_id', $chainId);
              });
        });
    }
}