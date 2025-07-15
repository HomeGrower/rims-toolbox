<?php

namespace App\Filament\Admin\Resources\ModuleResource\Pages;

use App\Filament\Admin\Resources\ModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateModule extends CreateRecord
{
    protected static string $resource = ModuleResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Transform setup_fields from array format to key-value format for storage
        if (isset($data['setup_fields']) && is_array($data['setup_fields'])) {
            $fields = [];
            foreach ($data['setup_fields'] as $field) {
                if (isset($field['field_name'])) {
                    $fields[$field['field_name']] = [
                        'type' => $field['type'] ?? 'text',
                        'label' => $field['label'] ?? $field['field_name'],
                        'description' => $field['description'] ?? '',
                        'required' => $field['required'] ?? false,
                        'validation' => $field['validation'] ?? null,
                        'options' => $field['options'] ?? [],
                        'default_value' => $field['default_value'] ?? null,
                    ];
                }
            }
            $data['setup_fields'] = $fields;
        }
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
