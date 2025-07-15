<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleBrandConfiguration extends Model
{
    protected $fillable = [
        'module_id',
        'hotel_brand_id',
        'additional_fields',
        'field_overrides',
        'conditional_fields',
        'layout_settings',
        'dependencies',
        'is_active',
    ];

    protected $casts = [
        'additional_fields' => 'array',
        'field_overrides' => 'array',
        'conditional_fields' => 'array',
        'layout_settings' => 'array',
        'dependencies' => 'array',
        'is_active' => 'boolean',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function hotelBrand(): BelongsTo
    {
        return $this->belongsTo(HotelBrand::class);
    }

    /**
     * Get effective fields for this module+brand combination
     */
    public function getEffectiveFields(array $baseModuleFields, array $context = []): array
    {
        $fields = $baseModuleFields;

        // Add additional fields for this brand
        if (!empty($this->additional_fields)) {
            foreach ($this->additional_fields as $fieldKey => $fieldConfig) {
                $fields[$fieldKey] = $fieldConfig;
            }
        }

        // Apply field overrides
        if (!empty($this->field_overrides)) {
            foreach ($this->field_overrides as $fieldKey => $overrides) {
                if (isset($fields[$fieldKey])) {
                    $fields[$fieldKey] = array_merge($fields[$fieldKey], $overrides);
                }
            }
        }

        // Apply conditional fields based on context
        if (!empty($this->conditional_fields)) {
            foreach ($this->conditional_fields as $condition) {
                if ($this->evaluateCondition($condition['when'] ?? [], $context)) {
                    foreach ($condition['fields'] ?? [] as $fieldKey => $fieldConfig) {
                        $fields[$fieldKey] = $fieldConfig;
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Example configuration for Voco + Room Upsell
     */
    public static function getVocoRoomUpsellConfig(): array
    {
        return [
            'additional_fields' => [
                'lifestyle_images' => [
                    'type' => 'files',
                    'label' => 'Voco Lifestyle Images',
                    'description' => 'Upload lifestyle images showing the Voco experience',
                    'required' => true,
                    'accept' => 'image/*',
                    'multiple' => true,
                    'min_files' => 3,
                ],
                'voco_color_scheme' => [
                    'type' => 'select',
                    'label' => 'Voco Color Scheme',
                    'options' => ['Vibrant Yellow', 'Cool Blue', 'Natural Green'],
                    'required' => true,
                ],
            ],
            'layout_settings' => [
                'template' => 'voco-modern',
                'show_lifestyle_gallery' => true,
                'emphasis_on_experience' => true,
            ],
        ];
    }

    /**
     * Example for Anantara + Room Pictures
     */
    public static function getAnantaraRoomPicturesConfig(): array
    {
        return [
            'additional_fields' => [
                'room_floorplans' => [
                    'type' => 'files',
                    'label' => 'Room Floor Plans',
                    'description' => 'Upload floor plans for each room category',
                    'required' => true,
                    'accept' => 'image/*,application/pdf',
                    'multiple' => true,
                ],
                'room_360_views' => [
                    'type' => 'url',
                    'label' => '360° Room View URLs',
                    'description' => 'Links to 360° virtual tours for each room type',
                    'required' => false,
                ],
            ],
            'conditional_fields' => [
                [
                    'when' => ['module' => 'room_upsell'],
                    'fields' => [
                        'luxury_amenities_list' => [
                            'type' => 'textarea',
                            'label' => 'Luxury Amenities Description',
                            'description' => 'Detailed description of luxury amenities for upselling',
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function evaluateCondition(array $conditions, array $context): bool
    {
        foreach ($conditions as $key => $value) {
            if (!isset($context[$key]) || $context[$key] !== $value) {
                return false;
            }
        }
        return true;
    }
}