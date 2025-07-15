<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PmsType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'setup_requirements',
        'module_configurations',
        'brand_configurations',
        'reservation_settings_config',
        'policy_example_images',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'setup_requirements' => 'array',
        'module_configurations' => 'array',
        'brand_configurations' => 'array',
        'reservation_settings_config' => 'array',
        'policy_example_images' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pmsType) {
            if (empty($pmsType->code)) {
                $pmsType->code = static::generateUniqueCode($pmsType->name);
            }
        });
    }

    public static function generateUniqueCode($name): string
    {
        $baseCode = strtoupper(Str::slug($name, '_'));
        $code = $baseCode;
        $counter = 1;

        while (static::where('code', $code)->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;
        }

        return $code;
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    public function getSetupRequirementsForModule($moduleSlug, $brandId = null): array
    {
        $requirements = [];

        // General setup requirements
        if (!empty($this->setup_requirements)) {
            $requirements = array_merge($requirements, $this->setup_requirements);
        }

        // Module-specific requirements
        if (!empty($this->module_configurations[$moduleSlug])) {
            $requirements = array_merge($requirements, $this->module_configurations[$moduleSlug]);
        }

        // Brand-specific requirements
        if ($brandId && !empty($this->brand_configurations[$brandId])) {
            $requirements = array_merge($requirements, $this->brand_configurations[$brandId]);
        }

        return $requirements;
    }

    public function shouldShowReservationPolicyField($field): bool
    {
        if (empty($this->reservation_settings_config)) {
            // Default behavior for Opera and OHIP if not configured
            if (in_array($this->code, ['OPERA_ONPREM', 'OHIP'])) {
                return true;
            }
            return false;
        }

        // Check if reservation settings are active
        if (isset($this->reservation_settings_config['is_active']) && !$this->reservation_settings_config['is_active']) {
            return false;
        }

        return $this->reservation_settings_config[$field] ?? false;
    }

    public function isReservationSettingsActive(): bool
    {
        if (empty($this->reservation_settings_config)) {
            // Default behavior for Opera and OHIP if not configured
            if (in_array($this->code, ['OPERA_ONPREM', 'OHIP'])) {
                return true;
            }
            return false;
        }

        return $this->reservation_settings_config['is_active'] ?? true;
    }

    public function getPolicyExampleImage($policyType): ?string
    {
        if (empty($this->policy_example_images)) {
            return null;
        }

        return $this->policy_example_images[$policyType] ?? null;
    }
}