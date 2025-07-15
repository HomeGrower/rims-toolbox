<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandConfiguration extends Model
{
    protected $fillable = [
        'hotel_brand_id',
        'configuration_type',
        'team',
        'settings',
        'instructions',
        'additional_fields',
        'field_overrides',
        'overrides_chain',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'instructions' => 'array',
        'additional_fields' => 'array',
        'field_overrides' => 'array',
        'overrides_chain' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function hotelBrand(): BelongsTo
    {
        return $this->belongsTo(HotelBrand::class);
    }

    /**
     * Get the effective configuration by merging with chain config
     */
    public function getEffectiveConfiguration(ChainConfiguration $chainConfig = null): array
    {
        if (!$chainConfig || $this->overrides_chain) {
            // Use only brand configuration
            return [
                'settings' => $this->settings ?? [],
                'instructions' => $this->instructions ?? [],
                'additional_fields' => $this->additional_fields ?? [],
                'field_overrides' => $this->field_overrides ?? [],
            ];
        }

        // Merge with chain configuration
        return [
            'settings' => array_merge($chainConfig->settings ?? [], $this->settings ?? []),
            'instructions' => array_merge($chainConfig->instructions ?? [], $this->instructions ?? []),
            'additional_fields' => array_merge($chainConfig->additional_fields ?? [], $this->additional_fields ?? []),
            'field_overrides' => array_merge($chainConfig->field_overrides ?? [], $this->field_overrides ?? []),
        ];
    }
}