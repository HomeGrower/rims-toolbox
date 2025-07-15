<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    public static function getForSelect()
    {
        return static::active()
            ->ordered()
            ->pluck('name', 'code')
            ->map(function ($name, $code) {
                return "{$code} - {$name}";
            })
            ->toArray();
    }

    public function getDisplayNameAttribute()
    {
        return "{$this->code} - {$this->name}";
    }
}
