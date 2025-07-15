<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GreetingParagraph extends Model
{
    protected $fillable = [
        'project_id',
        'paragraph_number',
        'priority',
        'content',
        'modules',
        'show_if_conditions',
        'hide_if_conditions',
        'is_active',
    ];

    protected $casts = [
        'modules' => 'array',
        'show_if_conditions' => 'array',
        'hide_if_conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForParagraph($query, $number)
    {
        return $query->where('paragraph_number', $number);
    }

    public function scopeForModule($query, $moduleId)
    {
        return $query->where(function ($q) use ($moduleId) {
            $q->whereNull('modules')
              ->orWhereJsonContains('modules', $moduleId)
              ->orWhereJsonContains('modules', (string)$moduleId);
        });
    }

    public function appliesToModule($moduleId): bool
    {
        if (empty($this->modules)) {
            return true; // Applies to all modules if none specified
        }
        
        return in_array($moduleId, $this->modules) || in_array((string)$moduleId, $this->modules);
    }

    public function meetsConditions(array $activeConditions): bool
    {
        // Check show_if conditions
        if (!empty($this->show_if_conditions)) {
            $hasRequiredCondition = false;
            foreach ($this->show_if_conditions as $conditionId) {
                if (in_array($conditionId, $activeConditions)) {
                    $hasRequiredCondition = true;
                    break;
                }
            }
            if (!$hasRequiredCondition) {
                return false;
            }
        }
        
        // Check hide_if conditions
        if (!empty($this->hide_if_conditions)) {
            foreach ($this->hide_if_conditions as $conditionId) {
                if (in_array($conditionId, $activeConditions)) {
                    return false;
                }
            }
        }
        
        return true;
    }
}