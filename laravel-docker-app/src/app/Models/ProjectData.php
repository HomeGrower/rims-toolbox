<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectData extends Model
{
    protected $fillable = [
        'project_id',
        'team',
        'section',
        'field_key',
        'field_label',
        'field_value',
        'field_type',
    ];

    protected $casts = [
        'field_value' => 'string',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get formatted value based on field type
     */
    public function getFormattedValueAttribute()
    {
        if (!$this->field_value) {
            return '-';
        }

        switch ($this->field_type) {
            case 'select':
                // For select fields, we might store the key but want to display the label
                if ($this->field_key === 'currency') {
                    $currency = Currency::where('code', $this->field_value)->first();
                    return $currency ? $currency->display_name : $this->field_value;
                }
                if ($this->field_key === 'primary_language' || in_array($this->field_key, ['languages'])) {
                    $language = Language::find($this->field_value);
                    return $language ? $language->name : $this->field_value;
                }
                return $this->field_value;
                
            case 'checkbox':
                return $this->field_value ? 'Yes' : 'No';
                
            case 'url':
                return '<a href="' . $this->field_value . '" target="_blank" class="text-blue-600 hover:underline">' . $this->field_value . '</a>';
                
            case 'email':
                return '<a href="mailto:' . $this->field_value . '" class="text-blue-600 hover:underline">' . $this->field_value . '</a>';
                
            default:
                return $this->field_value;
        }
    }

    /**
     * Get all data for a project grouped by team and section
     */
    public static function getGroupedDataForProject($projectId)
    {
        $data = static::where('project_id', $projectId)
            ->orderBy('team')
            ->orderBy('section')
            ->get();

        $grouped = [];
        foreach ($data as $item) {
            if (!isset($grouped[$item->team])) {
                $grouped[$item->team] = [];
            }
            if (!isset($grouped[$item->team][$item->section])) {
                $grouped[$item->team][$item->section] = [];
            }
            $grouped[$item->team][$item->section][] = $item;
        }

        return $grouped;
    }
}
