<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Module;
use App\Models\ChainConfiguration;
use App\Models\BrandConfiguration;
use App\Models\ModuleBrandConfiguration;
use App\Models\ProjectSetupTeam;

class ConfigurationService
{
    /**
     * Get the effective fields for a project setup section
     */
    public function getFieldsForSetupSection(Project $project, string $team, string $section): array
    {
        // 1. Start with base fields
        $baseFields = ProjectSetupTeam::generateFieldsForSection($team, $section, $project);
        
        // 2. Apply chain-level configurations
        $chainConfig = $this->getChainConfiguration($project, $team, $section);
        if ($chainConfig) {
            $baseFields = $chainConfig->getMergedFields($baseFields);
        }
        
        // 3. Apply brand-level configurations (overrides chain)
        $brandConfig = $this->getBrandConfiguration($project, $team, $section);
        if ($brandConfig) {
            $effectiveConfig = $brandConfig->getEffectiveConfiguration($chainConfig);
            
            // Apply additional fields
            if (!empty($effectiveConfig['additional_fields'])) {
                foreach ($effectiveConfig['additional_fields'] as $fieldKey => $fieldConfig) {
                    $baseFields[$fieldKey] = $fieldConfig;
                }
            }
            
            // Apply field overrides
            if (!empty($effectiveConfig['field_overrides'])) {
                foreach ($effectiveConfig['field_overrides'] as $fieldKey => $overrides) {
                    if (isset($baseFields[$fieldKey])) {
                        $baseFields[$fieldKey] = array_merge($baseFields[$fieldKey], $overrides);
                    }
                }
            }
        }
        
        return $baseFields;
    }
    
    /**
     * Get the effective fields for a module in a project
     */
    public function getFieldsForModule(Project $project, Module $module): array
    {
        // 1. Start with module base fields
        $baseFields = $module->setup_fields ?? [];
        
        // 2. Apply module+brand specific configurations
        if ($project->hotel_brand_id) {
            $moduleBrandConfig = ModuleBrandConfiguration::where('module_id', $module->id)
                ->where('hotel_brand_id', $project->hotel_brand_id)
                ->where('is_active', true)
                ->first();
                
            if ($moduleBrandConfig) {
                $context = [
                    'module' => $module->slug,
                    'brand' => $project->hotelBrand->code ?? null,
                    'chain' => $project->hotelChain->code ?? null,
                    'primary_language' => $project->primary_language ?? 'en',
                    'languages' => $project->languages ?? [],
                ];
                
                $baseFields = $moduleBrandConfig->getEffectiveFields($baseFields, $context);
            }
        }
        
        // 3. Check for module dependencies and add their requirements
        $dependencies = $this->getModuleDependencies($module, $project);
        foreach ($dependencies as $dependency) {
            if (isset($dependency['required_fields'])) {
                foreach ($dependency['required_fields'] as $fieldKey => $fieldConfig) {
                    if (!isset($baseFields[$fieldKey])) {
                        $baseFields[$fieldKey] = $fieldConfig;
                    }
                }
            }
        }
        
        return $baseFields;
    }
    
    /**
     * Get instructions for a specific team and configuration
     */
    public function getInstructionsForTeam(Project $project, string $team, string $configurationType): array
    {
        $instructions = [];
        
        // Get chain instructions
        $chainConfig = ChainConfiguration::where('hotel_chain_id', $project->hotel_chain_id)
            ->where('configuration_type', $configurationType)
            ->where('team', $team)
            ->where('is_active', true)
            ->first();
            
        if ($chainConfig && !empty($chainConfig->instructions)) {
            $instructions = array_merge($instructions, $chainConfig->instructions);
        }
        
        // Get brand instructions (may override)
        $brandConfig = BrandConfiguration::where('hotel_brand_id', $project->hotel_brand_id)
            ->where('configuration_type', $configurationType)
            ->where('team', $team)
            ->where('is_active', true)
            ->first();
            
        if ($brandConfig) {
            $effectiveConfig = $brandConfig->getEffectiveConfiguration($chainConfig);
            if (!empty($effectiveConfig['instructions'])) {
                $instructions = $effectiveConfig['instructions'];
            }
        }
        
        return $instructions;
    }
    
    /**
     * Get module dependencies with their configurations
     */
    public function getModuleDependencies(Module $module, Project $project): array
    {
        $dependencies = [];
        
        // Get base module dependencies
        if (!empty($module->dependencies)) {
            foreach ($module->dependencies as $depSlug) {
                $depModule = Module::where('slug', $depSlug)->first();
                if ($depModule) {
                    $dependencies[] = [
                        'module' => $depModule,
                        'type' => 'required',
                        'source' => 'base_module',
                    ];
                }
            }
        }
        
        // Check for brand-specific additional dependencies
        if ($project->hotel_brand_id) {
            $moduleBrandConfig = ModuleBrandConfiguration::where('module_id', $module->id)
                ->where('hotel_brand_id', $project->hotel_brand_id)
                ->where('is_active', true)
                ->first();
                
            if ($moduleBrandConfig && !empty($moduleBrandConfig->dependencies)) {
                foreach ($moduleBrandConfig->dependencies as $dep) {
                    $depModule = Module::where('slug', $dep['module_slug'] ?? $dep)->first();
                    if ($depModule) {
                        $dependencies[] = [
                            'module' => $depModule,
                            'type' => $dep['type'] ?? 'required',
                            'source' => 'brand_config',
                            'required_fields' => $dep['required_fields'] ?? [],
                        ];
                    }
                }
            }
        }
        
        return $dependencies;
    }
    
    private function getChainConfiguration(Project $project, string $team, string $section): ?ChainConfiguration
    {
        if (!$project->hotel_chain_id) {
            return null;
        }
        
        // Map section to configuration type
        $configType = $this->mapSectionToConfigType($team, $section);
        
        return ChainConfiguration::where('hotel_chain_id', $project->hotel_chain_id)
            ->where('configuration_type', $configType)
            ->where('team', $team)
            ->where('is_active', true)
            ->first();
    }
    
    private function getBrandConfiguration(Project $project, string $team, string $section): ?BrandConfiguration
    {
        if (!$project->hotel_brand_id) {
            return null;
        }
        
        $configType = $this->mapSectionToConfigType($team, $section);
        
        return BrandConfiguration::where('hotel_brand_id', $project->hotel_brand_id)
            ->where('configuration_type', $configType)
            ->where('team', $team)
            ->where('is_active', true)
            ->first();
    }
    
    private function mapSectionToConfigType(string $team, string $section): string
    {
        $mapping = [
            'it' => [
                'email_settings' => 'email',
                'pms_settings' => 'it_setup',
                'security_settings' => 'it_setup',
            ],
            'reservation' => [
                'hotel_settings' => 'reservation',
                'user_settings' => 'reservation',
                'reservation_settings' => 'reservation',
            ],
            'marketing' => [
                'banner_pictures' => 'marketing',
                'logos' => 'marketing',
                'colors_fonts' => 'marketing',
                'room_pictures_texts' => 'marketing',
                'greetings_texts' => 'marketing',
                'promotions' => 'marketing',
            ],
        ];
        
        return $mapping[$team][$section] ?? $section;
    }
}