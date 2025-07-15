<?php

namespace App\Filament\Admin\Resources\ProjectResource\Pages;

use App\Filament\Admin\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        // Auto-generate name if empty
        if (empty($data['name']) && !empty($data['hotel_name']) && !empty($data['project_type'])) {
            $data['name'] = $data['hotel_name'] . ' - ' . ucfirst(str_replace('_', ' ', $data['project_type']));
        }

        return $data;
    }
}
