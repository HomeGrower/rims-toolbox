<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistTemplate extends Model
{
    protected $fillable = [
        'question',
        'description',
        'category',
        'type',
        'options',
        'module_mappings',
        'sort_order',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'module_mappings' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(ChecklistResponse::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function getModulesForResponse($response): array
    {
        if (!$this->module_mappings) {
            return [];
        }

        $modules = [];

        switch ($this->type) {
            case 'boolean':
                $key = $response ? 'true' : 'false';
                if (isset($this->module_mappings[$key])) {
                    $modules = $this->module_mappings[$key];
                }
                break;

            case 'select':
                if (isset($this->module_mappings[$response])) {
                    $modules = $this->module_mappings[$response];
                }
                break;

            case 'multiselect':
                if (is_array($response) && count($response) > 1 && isset($this->module_mappings['multiple'])) {
                    $modules = $this->module_mappings['multiple'];
                }
                break;
        }

        return $modules;
    }
}