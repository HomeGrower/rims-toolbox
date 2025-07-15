<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotelBrand extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_chain_id',
        'name',
        'code',
        'logo',
        'additional_modules',
        'brand_specific_questions',
        'it_configuration',
        'primary_color',
        'secondary_color',
        'font_family',
        'heading_font_family',
        'example_images',
        'promotions',
        'template_examples',
        'datastore_tables',
        'custom_datastore_tables',
        'is_active',
    ];

    protected $casts = [
        'additional_modules' => 'array',
        'brand_specific_questions' => 'array',
        'it_configuration' => 'array',
        'example_images' => 'array',
        'promotions' => 'array',
        'template_examples' => 'array',
        'datastore_tables' => 'array',
        'custom_datastore_tables' => 'array',
        'is_active' => 'boolean',
    ];

    public function hotelChain(): BelongsTo
    {
        return $this->belongsTo(HotelChain::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function getAdditionalModulesAttribute($value): array
    {
        return json_decode($value, true) ?? [];
    }

    public function getBrandSpecificQuestionsAttribute($value): array
    {
        return json_decode($value, true) ?? [];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getAllModules(): array
    {
        $chainModules = $this->hotelChain->default_modules ?? [];
        $brandModules = $this->additional_modules ?? [];
        
        return array_unique(array_merge($chainModules, $brandModules));
    }

    /**
     * Get the logo URL attribute
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        // Otherwise, generate the storage URL
        return \Storage::url($this->logo);
    }
}