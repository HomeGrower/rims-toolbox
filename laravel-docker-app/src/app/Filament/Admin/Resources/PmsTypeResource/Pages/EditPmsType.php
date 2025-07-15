<?php

namespace App\Filament\Admin\Resources\PmsTypeResource\Pages;

use App\Filament\Admin\Resources\PmsTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPmsType extends EditRecord
{
    protected static string $resource = PmsTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Keep setup_requirements as array format
        // No transformation needed since we're storing as array
        
        // Transform module_configurations
        if (isset($data['module_configurations']) && is_array($data['module_configurations'])) {
            $moduleConfigs = [];
            foreach ($data['module_configurations'] as $moduleSlug => $requirements) {
                $reqArray = [];
                foreach ($requirements as $fieldName => $config) {
                    $reqArray[] = [
                        'field_name' => $fieldName,
                        'type' => $config['type'] ?? 'text',
                        'label' => $config['label'] ?? $fieldName,
                        'description' => $config['description'] ?? '',
                        'required' => $config['required'] ?? false,
                    ];
                }
                $moduleConfigs[] = [
                    'module' => $moduleSlug,
                    'requirements' => $reqArray,
                ];
            }
            $data['module_configurations'] = $moduleConfigs;
        }
        
        // Transform brand_configurations
        if (isset($data['brand_configurations']) && is_array($data['brand_configurations'])) {
            $brandConfigs = [];
            foreach ($data['brand_configurations'] as $brandId => $requirements) {
                $reqArray = [];
                foreach ($requirements as $fieldName => $config) {
                    $reqArray[] = [
                        'field_name' => $fieldName,
                        'type' => $config['type'] ?? 'text',
                        'label' => $config['label'] ?? $fieldName,
                        'description' => $config['description'] ?? '',
                        'required' => $config['required'] ?? false,
                    ];
                }
                $brandConfigs[] = [
                    'brand' => $brandId,
                    'requirements' => $reqArray,
                ];
            }
            $data['brand_configurations'] = $brandConfigs;
        }
        
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Keep setup_requirements as array format (don't transform to key-value)
        // This ensures conditions are properly saved
        
        // Ensure newlines in descriptions are preserved
        if (isset($data['setup_requirements']) && is_array($data['setup_requirements'])) {
            foreach ($data['setup_requirements'] as &$requirement) {
                // Process main description
                if (isset($requirement['description'])) {
                    // Don't modify - let it save as-is
                }
                
                // Process conditional fields descriptions
                if (isset($requirement['conditions']) && is_array($requirement['conditions'])) {
                    foreach ($requirement['conditions'] as &$condition) {
                        if (isset($condition['fields']) && is_array($condition['fields'])) {
                            foreach ($condition['fields'] as &$field) {
                                if (isset($field['description'])) {
                                    // Don't modify - let it save as-is
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Transform module_configurations
        if (isset($data['module_configurations']) && is_array($data['module_configurations'])) {
            $moduleConfigs = [];
            foreach ($data['module_configurations'] as $config) {
                if (isset($config['module']) && isset($config['requirements'])) {
                    $requirements = [];
                    foreach ($config['requirements'] as $req) {
                        if (isset($req['field_name'])) {
                            $requirements[$req['field_name']] = [
                                'type' => $req['type'] ?? 'text',
                                'label' => $req['label'] ?? $req['field_name'],
                                'description' => $req['description'] ?? '',
                                'required' => $req['required'] ?? false,
                            ];
                        }
                    }
                    $moduleConfigs[$config['module']] = $requirements;
                }
            }
            $data['module_configurations'] = $moduleConfigs;
        }
        
        // Transform brand_configurations
        if (isset($data['brand_configurations']) && is_array($data['brand_configurations'])) {
            $brandConfigs = [];
            foreach ($data['brand_configurations'] as $config) {
                if (isset($config['brand']) && isset($config['requirements'])) {
                    $requirements = [];
                    foreach ($config['requirements'] as $req) {
                        if (isset($req['field_name'])) {
                            $requirements[$req['field_name']] = [
                                'type' => $req['type'] ?? 'text',
                                'label' => $req['label'] ?? $req['field_name'],
                                'description' => $req['description'] ?? '',
                                'required' => $req['required'] ?? false,
                            ];
                        }
                    }
                    $brandConfigs[$config['brand']] = $requirements;
                }
            }
            $data['brand_configurations'] = $brandConfigs;
        }
        
        return $data;
    }
}