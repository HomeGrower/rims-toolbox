<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Module extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'code',
        'module_category_id',
        'description',
        'category',
        'sort_order',
        'is_active',
        'dependencies',
        'settings',
        'datastore_tables',
        'available_for_chains',
        'available_for_brands',
        'required_questions',
        'conditional_questions',
        'required_documents',
        'setup_fields',
        'requires_room_details',
        'requires_room_short_description',
        'requires_room_long_description',
        'requires_room_main_image',
        'requires_room_slideshow_images',
        'allow_room_details_toggle',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'dependencies' => 'array',
        'settings' => 'array',
        'datastore_tables' => 'array',
        'available_for_chains' => 'array',
        'available_for_brands' => 'array',
        'required_questions' => 'array',
        'conditional_questions' => 'array',
        'required_documents' => 'array',
        'setup_fields' => 'array',
        'requires_room_details' => 'boolean',
        'requires_room_short_description' => 'boolean',
        'requires_room_long_description' => 'boolean',
        'requires_room_main_image' => 'boolean',
        'requires_room_slideshow_images' => 'boolean',
        'allow_room_details_toggle' => 'boolean',
    ];
    
    const CATEGORIES = [
        'single_message' => 'Single Message',
        'mailing' => 'Mailing',
        'landingpage' => 'Landing Page',
        'form' => 'Form',
        'development' => 'Development',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($module) {
            if (empty($module->slug)) {
                $module->slug = Str::slug($module->name);
            }
        });
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_modules')
            ->withPivot(['status', 'progress', 'configuration', 'tasks', 'started_at', 'completed_at'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function hasDependencies(): bool
    {
        return !empty($this->dependencies);
    }

    public function getDependentModules()
    {
        if (!$this->hasDependencies()) {
            return collect();
        }

        return static::whereIn('slug', $this->dependencies)->get();
    }

    public function moduleCategory(): BelongsTo
    {
        return $this->belongsTo(ModuleCategory::class);
    }

    public function documentUploads(): HasMany
    {
        return $this->hasMany(DocumentUpload::class);
    }

    public function isAvailableForChain(?HotelChain $chain): bool
    {
        if (!$chain) {
            return true;
        }

        if (empty($this->available_for_chains)) {
            return true;
        }

        return in_array($chain->id, $this->available_for_chains);
    }

    public function isAvailableForBrand(?HotelBrand $brand): bool
    {
        if (!$brand) {
            return true;
        }

        if (empty($this->available_for_brands)) {
            return true;
        }

        return in_array($brand->id, $this->available_for_brands);
    }

    public function getQuestionsForProject(Project $project): array
    {
        $questions = $this->required_questions ?? [];
        
        if ($project->hotelChain && !empty($this->conditional_questions['by_chain'][$project->hotelChain->id])) {
            $questions = array_merge($questions, $this->conditional_questions['by_chain'][$project->hotelChain->id]);
        }
        
        if ($project->hotelBrand && !empty($this->conditional_questions['by_brand'][$project->hotelBrand->id])) {
            $questions = array_merge($questions, $this->conditional_questions['by_brand'][$project->hotelBrand->id]);
        }
        
        return $questions;
    }
}