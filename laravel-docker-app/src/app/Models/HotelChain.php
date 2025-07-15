<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotelChain extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'default_modules',
        'required_documents',
        'custom_datastore_tables',
        'is_active',
    ];

    protected $casts = [
        'default_modules' => 'array',
        'required_documents' => 'array',
        'custom_datastore_tables' => 'array',
        'is_active' => 'boolean',
    ];

    public function brands(): HasMany
    {
        return $this->hasMany(HotelBrand::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
    
    public function conditions(): HasMany
    {
        return $this->hasMany(Condition::class);
    }

    public function getDefaultModulesAttribute($value): array
    {
        return json_decode($value, true) ?? [];
    }

    public function getRequiredDocumentsAttribute($value): array
    {
        return json_decode($value, true) ?? [];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}