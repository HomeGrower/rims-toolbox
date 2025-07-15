<?php

namespace App\Filament\Admin\Resources\PmsTypeResource\Pages;

use App\Filament\Admin\Resources\PmsTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePmsType extends CreateRecord
{
    protected static string $resource = PmsTypeResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Transform setup_requirements from array format to key-value format
        if (isset($data['setup_requirements']) && is_array($data['setup_requirements'])) {
            $requirements = [];
            foreach ($data['setup_requirements'] as $req) {
                if (isset($req['field_name'])) {
                    $requirements[$req['field_name']] = [
                        'type' => $req['type'] ?? 'text',
                        'label' => $req['label'] ?? $req['field_name'],
                        'description' => $req['description'] ?? '',
                        'required' => $req['required'] ?? false,
                        'validation' => $req['validation'] ?? null,
                        'options' => $req['options'] ?? [],
                    ];
                }
            }
            $data['setup_requirements'] = $requirements;
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
