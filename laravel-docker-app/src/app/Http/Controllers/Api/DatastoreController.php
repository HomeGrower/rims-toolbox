<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\DatastoreConfiguration;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DatastoreController extends Controller
{
    /**
     * Get project datastore information
     */
    public function getProjectDatastoreInfo(Request $request, Project $project)
    {
        $project->load(['modules', 'hotelBrand', 'hotelChain']);
        
        return response()->json([
            'modules' => $project->modules->map(function ($module) {
                return [
                    'id' => $module->id,
                    'code' => $module->code,
                    'name' => $module->name,
                    'datastore_tables' => $module->datastore_tables ?? []
                ];
            }),
            'brand' => $project->hotelBrand ? [
                'id' => $project->hotelBrand->id,
                'name' => $project->hotelBrand->name,
                'datastore_tables' => $project->hotelBrand->datastore_tables ?? [],
                'custom_datastore_tables' => $project->hotelBrand->custom_datastore_tables ?? []
            ] : null,
            'chain' => $project->hotelChain ? [
                'id' => $project->hotelChain->id,
                'name' => $project->hotelChain->name,
                'code' => $project->hotelChain->code,
                'custom_datastore_tables' => $project->hotelChain->custom_datastore_tables ?? []
            ] : null,
            'standardTables' => Setting::get('standard_datastore_tables', [
                'properties',
                'rooms',
                'roomCategories',
                'buildings',
                'taxes',
                'cancellationPolicies',
                'colors',
                'tagMapping'
            ])
        ]);
    }

    /**
     * Get or create datastore configuration
     */
    public function getConfiguration(Request $request, Project $project)
    {
        $configuration = DatastoreConfiguration::firstOrCreate(
            ['project_id' => $project->id],
            [
                'name' => 'Default Configuration',
                'description' => 'Auto-generated configuration',
                'is_active' => true,
                'version' => 1
            ]
        );

        // Load the master template
        $masterTemplate = $this->loadMasterTemplate();
        
        // Get selected tables based on general settings and modules
        $selectedTables = $this->getSelectedTablesForProject($project);
        
        // Build the complete table structure
        $tables = [];
        
        // First, add all tables from master template
        foreach ($masterTemplate['tables'] ?? [] as $tableKey => $tableConfig) {
            $tables[$tableKey] = [
                'label' => $tableConfig['label'] ?? ucfirst($tableKey),
                'icon' => $tableConfig['icon'] ?? 'fa-table',
                'description' => $tableConfig['description'] ?? '',
                'fields' => $tableConfig['fields'] ?? [],
                'disabled' => !in_array($tableKey, $selectedTables), // Disable if not selected
                'required' => $this->isRequiredTable($tableKey)
            ];
        }
        
        // Add custom brand tables
        if ($project->hotelBrand && !empty($project->hotelBrand->custom_datastore_tables)) {
            foreach ($project->hotelBrand->custom_datastore_tables as $customTable) {
                if (isset($customTable['name']) && !isset($tables[$customTable['name']])) {
                    $tables[$customTable['name']] = [
                        'label' => $customTable['label'] ?? ucfirst($customTable['name']),
                        'icon' => $customTable['icon'] ?? 'fa-table',
                        'description' => $customTable['description'] ?? '',
                        'fields' => $this->convertFieldsFormat($customTable['fields'] ?? []),
                        'disabled' => false, // Custom tables are enabled by default
                        'custom' => true
                    ];
                }
            }
        }
        
        // Add custom chain tables
        if ($project->hotelChain && !empty($project->hotelChain->custom_datastore_tables)) {
            foreach ($project->hotelChain->custom_datastore_tables as $customTable) {
                if (isset($customTable['name']) && !isset($tables[$customTable['name']])) {
                    $tables[$customTable['name']] = [
                        'label' => $customTable['label'] ?? ucfirst($customTable['name']),
                        'icon' => $customTable['icon'] ?? 'fa-table',
                        'description' => $customTable['description'] ?? '',
                        'fields' => $this->convertFieldsFormat($customTable['fields'] ?? []),
                        'disabled' => false,
                        'custom' => true
                    ];
                }
            }
        }
        
        // Apply any saved configuration overrides
        if ($configuration->configuration) {
            foreach ($configuration->configuration['tables'] ?? [] as $tableKey => $overrides) {
                if (isset($tables[$tableKey])) {
                    $tables[$tableKey] = array_merge($tables[$tableKey], $overrides);
                }
            }
        }

        return response()->json([
            'tables' => $tables,
            'languages' => $masterTemplate['languages'] ?? ['en']
        ]);
    }

    /**
     * Save datastore configuration
     */
    public function saveConfiguration(Request $request, Project $project)
    {
        $request->validate([
            'configuration' => 'required|array'
        ]);

        $configuration = DatastoreConfiguration::firstOrCreate(
            ['project_id' => $project->id],
            [
                'name' => 'Default Configuration',
                'description' => 'Auto-generated configuration',
                'is_active' => true,
                'version' => 1
            ]
        );

        $configuration->configuration = $request->input('configuration');
        $configuration->save();

        return response()->json([
            'message' => 'Configuration saved successfully',
            'configuration' => $configuration
        ]);
    }

    /**
     * Load master template
     */
    private function loadMasterTemplate(): array
    {
        $paths = [
            base_path('vorlage/SINCP_Datastore_Structure.json'),
            base_path('vorlage/Datastore_Structure.json'),
            storage_path('app/vorlage/DEFAULT_Datastore_structure.json'),
        ];
        
        foreach ($paths as $path) {
            if (File::exists($path)) {
                return json_decode(File::get($path), true);
            }
        }
        
        return ['tables' => []];
    }

    /**
     * Check if a table is required
     */
    private function isRequiredTable(string $tableKey): bool
    {
        $requiredTables = Setting::get('required_datastore_tables', [
            'properties'
        ]);
        
        return in_array($tableKey, $requiredTables);
    }

    /**
     * Convert fields from array format to object format
     */
    private function convertFieldsFormat(array $fields): array
    {
        $convertedFields = [];
        
        foreach ($fields as $field) {
            if (isset($field['name'])) {
                $fieldKey = $field['name'];
                unset($field['name']);
                $convertedFields[$fieldKey] = $field;
            }
        }
        
        return $convertedFields;
    }
    
    /**
     * Get selected tables for a project based on general settings and modules
     */
    private function getSelectedTablesForProject(Project $project): array
    {
        $selectedTables = [];
        
        // Get tables from general settings
        $standardTables = Setting::get('standard_datastore_tables', []);
        $selectedTables = array_merge($selectedTables, $standardTables);
        
        // Get tables from selected modules
        $project->load('modules');
        foreach ($project->modules as $module) {
            if (!empty($module->datastore_tables)) {
                $selectedTables = array_merge($selectedTables, $module->datastore_tables);
            }
        }
        
        // Get tables from brand if available
        if ($project->hotelBrand && !empty($project->hotelBrand->datastore_tables)) {
            $selectedTables = array_merge($selectedTables, $project->hotelBrand->datastore_tables);
        }
        
        // Always include required tables
        $requiredTables = $this->isRequiredTable('dummy'); // Get required tables list
        $requiredTables = Setting::get('required_datastore_tables', [
            'properties'
        ]);
        $selectedTables = array_merge($selectedTables, $requiredTables);
        
        // Remove duplicates
        return array_unique($selectedTables);
    }
    
    /**
     * Preview the configuration overrides
     */
    public function preview(Request $request, Project $project)
    {
        $configuration = DatastoreConfiguration::firstOrCreate(
            ['project_id' => $project->id],
            [
                'name' => 'Default Configuration',
                'description' => 'Auto-generated configuration',
                'is_active' => true,
                'version' => 1
            ]
        );
        
        $overrides = $configuration->configuration ?? ['tables' => []];
        $jsonString = json_encode($overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        return response()->view('datastore-preview', [
            'project' => $project,
            'json' => $jsonString
        ]);
    }
}