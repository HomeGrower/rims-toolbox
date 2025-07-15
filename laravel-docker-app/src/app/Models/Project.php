<?php

namespace App\Models;

use App\Traits\CacheableModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Project extends Model
{
    use CacheableModel;
    protected $fillable = [
        'name',
        'hotel_name',
        'project_type',
        'access_code',
        'created_by',
        'delegated_to',
        'delegated_at',
        'hotel_chain_id',
        'hotel_brand_id',
        'pms_type_id',
        'status',
        'notes',
        'notification_emails',
        'languages',
        'primary_language',
        'metadata',
        'activated_at',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'notification_emails' => 'array',
        'languages' => 'array',
        'activated_at' => 'datetime',
        'completed_at' => 'datetime',
        'delegated_at' => 'datetime',
    ];
    
    // Removed overall_progress from appends to improve performance
    // It will be calculated only when explicitly accessed

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->access_code)) {
                $project->access_code = static::generateUniqueCode();
            }
            
            // Auto-generate name from hotel_name if empty
            if (empty($project->name) && !empty($project->hotel_name)) {
                $project->name = $project->hotel_name;
            }
        });
        
        static::updating(function ($project) {
            // Auto-generate name from hotel_name if empty during update
            if (empty($project->name) && !empty($project->hotel_name)) {
                $project->name = $project->hotel_name;
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('access_code', $code)->exists());

        return $code;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'project_modules')
            ->withPivot(['status', 'progress', 'configuration', 'tasks', 'started_at', 'completed_at'])
            ->withTimestamps();
    }

    public function checklistResponses(): HasMany
    {
        return $this->hasMany(ChecklistResponse::class);
    }

    public function hotelChain(): BelongsTo
    {
        return $this->belongsTo(HotelChain::class);
    }

    public function hotelBrand(): BelongsTo
    {
        return $this->belongsTo(HotelBrand::class);
    }

    public function documentUploads(): HasMany
    {
        return $this->hasMany(DocumentUpload::class);
    }

    public function setupTeams(): HasMany
    {
        return $this->hasMany(ProjectSetupTeam::class);
    }

    public function projectData(): HasMany
    {
        return $this->hasMany(ProjectData::class);
    }
    
    public function datastoreConfigurations(): HasMany
    {
        return $this->hasMany(DatastoreConfiguration::class);
    }

    public function pmsType(): BelongsTo
    {
        return $this->belongsTo(PmsType::class);
    }

    public function delegatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_to');
    }

    public function getResponsibleAdminAttribute(): User
    {
        return $this->delegatedTo ?? $this->creator;
    }

    public function isDelegated(): bool
    {
        return $this->delegated_to !== null;
    }

    public function delegateTo(User $user): void
    {
        $this->update([
            'delegated_to' => $user->id,
            'delegated_at' => now(),
        ]);
    }

    public function removeDelegation(): void
    {
        $this->update([
            'delegated_to' => null,
            'delegated_at' => null,
        ]);
    }

    public function getOverallProgressAttribute(): int
    {
        // Load setup teams if not already loaded
        $this->loadMissing('setupTeams');
        
        $setupTeams = $this->setupTeams;
        
        if ($setupTeams->isEmpty()) {
            return 0;
        }
        
        // Calculate average progress across all setup sections
        $totalProgress = 0;
        $sectionCount = 0;
        
        foreach ($setupTeams as $setupTeam) {
            // For greetings, calculate progress without saving
            if ($setupTeam->section === 'greetings_texts') {
                $progress = $setupTeam->calculateProgress();
                $totalProgress += $progress;
            } else {
                $totalProgress += $setupTeam->progress;
            }
            $sectionCount++;
        }
        
        return $sectionCount > 0 ? (int) round($totalProgress / $sectionCount) : 0;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}