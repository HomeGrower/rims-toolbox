<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;

class DatastoreConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'base_template',
        'configuration',
        'disabled_tables',
        'custom_fields',
        'module_overrides',
        'chain_overrides',
        'is_active',
        'version',
    ];

    protected $casts = [
        'base_template' => 'array',
        'configuration' => 'array',
        'disabled_tables' => 'array',
        'custom_fields' => 'array',
        'module_overrides' => 'array',
        'chain_overrides' => 'array',
        'is_active' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Load the master template from file
     */
    public static function loadMasterTemplate(): array
    {
        // Try multiple paths
        $paths = [
            base_path('vorlage/Datastore_Structure.json'),
            storage_path('app/vorlage/DEFAULT_Datastore_structure.json'),
        ];
        
        foreach ($paths as $path) {
            if (File::exists($path)) {
                return json_decode(File::get($path), true);
            }
        }
        
        return [];
    }

    /**
     * Get the compiled configuration (merged with base template)
     */
    public function getCompiledConfiguration(): array
    {
        $config = $this->base_template ?: self::loadMasterTemplate();
        
        // Add custom brand tables to the configuration
        if ($this->project && $this->project->hotelBrand && !empty($this->project->hotelBrand->custom_datastore_tables)) {
            foreach ($this->project->hotelBrand->custom_datastore_tables as $customTable) {
                if (isset($customTable['name']) && !isset($config['tables'][$customTable['name']])) {
                    $config['tables'][$customTable['name']] = [
                        'label' => $customTable['label'] ?? ucfirst($customTable['name']),
                        'icon' => $customTable['icon'] ?? 'fa-table',
                        'description' => $customTable['description'] ?? '',
                        'fields' => [], // Empty fields, can be configured later
                        'custom' => true, // Mark as custom table
                    ];
                }
            }
        }
        
        // First, disable all tables that are not in the allowed list
        $allowedTables = $this->getAllowedTables();
        foreach ($config['tables'] ?? [] as $tableName => $tableConfig) {
            if (!in_array($tableName, $allowedTables)) {
                $config['tables'][$tableName]['disabled'] = true;
            }
        }
        
        // Apply disabled tables
        if ($this->disabled_tables) {
            foreach ($this->disabled_tables as $table) {
                if (isset($config['tables'][$table])) {
                    $config['tables'][$table]['disabled'] = true;
                }
            }
        }
        
        // Apply custom fields
        if ($this->custom_fields) {
            foreach ($this->custom_fields as $table => $fields) {
                if (!isset($config['tables'][$table])) {
                    $config['tables'][$table] = [
                        'label' => ucfirst($table),
                        'fields' => []
                    ];
                }
                $config['tables'][$table]['fields'] = array_merge(
                    $config['tables'][$table]['fields'] ?? [],
                    $fields
                );
            }
        }
        
        // Apply module overrides
        if ($this->module_overrides && $this->project) {
            $activeModules = $this->project->modules->pluck('code')->toArray();
            foreach ($this->module_overrides as $module => $overrides) {
                if (in_array($module, $activeModules)) {
                    $config = $this->applyOverrides($config, $overrides);
                }
            }
        }
        
        // Apply chain overrides
        if ($this->chain_overrides && $this->project && $this->project->hotelChain) {
            $chainCode = $this->project->hotelChain->code;
            if (isset($this->chain_overrides[$chainCode])) {
                $config = $this->applyOverrides($config, $this->chain_overrides[$chainCode]);
            }
        }
        
        // Apply manual configuration last
        if ($this->configuration) {
            $config = array_replace_recursive($config, $this->configuration);
        }
        
        return $config;
    }

    /**
     * Apply overrides to configuration
     */
    private function applyOverrides(array $config, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if ($key === 'disabled_tables' && is_array($value)) {
                foreach ($value as $table) {
                    if (isset($config['tables'][$table])) {
                        $config['tables'][$table]['disabled'] = true;
                    }
                }
            } elseif ($key === 'custom_fields' && is_array($value)) {
                foreach ($value as $table => $fields) {
                    if (!isset($config['tables'][$table])) {
                        $config['tables'][$table] = [
                            'label' => ucfirst($table),
                            'fields' => []
                        ];
                    }
                    $config['tables'][$table]['fields'] = array_merge(
                        $config['tables'][$table]['fields'] ?? [],
                        $fields
                    );
                }
            } else {
                $config = array_replace_recursive($config, [$key => $value]);
            }
        }
        return $config;
    }

    /**
     * Export configuration to JSON file
     */
    public function exportToFile(string $path): bool
    {
        $config = $this->getCompiledConfiguration();
        return File::put($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Create a new version
     */
    public function createNewVersion(): self
    {
        $newVersion = $this->replicate();
        $newVersion->version = $this->version + 1;
        $newVersion->save();
        return $newVersion;
    }
    
    /**
     * Get allowed tables based on standard tables and module configurations
     */
    public function getAllowedTables(): array
    {
        // Start with standard tables from general settings
        $standardTables = Setting::get('standard_datastore_tables', [
            'properties',
            'rooms',
            'roomCategories',
            'buildings',
            'taxes',
            'cancellationPolicies',
            'colors',
            'tagMapping'
        ]);
        
        $allowedTables = $standardTables;
        
        // Add tables from active modules
        if ($this->project) {
            $modules = $this->project->modules;
            foreach ($modules as $module) {
                if (!empty($module->datastore_tables)) {
                    $allowedTables = array_merge($allowedTables, $module->datastore_tables);
                }
            }
            
            // Add tables from brand configuration
            if ($this->project->hotelBrand) {
                $brand = $this->project->hotelBrand;
                
                // Add brand-specific datastore tables
                if (!empty($brand->datastore_tables)) {
                    $allowedTables = array_merge($allowedTables, $brand->datastore_tables);
                }
                
                // Add custom brand tables (these need to be added to the master template dynamically)
                if (!empty($brand->custom_datastore_tables)) {
                    foreach ($brand->custom_datastore_tables as $customTable) {
                        if (isset($customTable['name'])) {
                            $allowedTables[] = $customTable['name'];
                        }
                    }
                }
            }
        }
        
        // Return unique tables
        return array_unique($allowedTables);
    }
}