<?php

namespace App\Filament\Admin\Resources\ProjectResource\Pages;

use App\Filament\Admin\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure we're not triggering unnecessary calculations
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        // Redirect back to the list after saving
        return $this->getResource()::getUrl('index');
    }
}
