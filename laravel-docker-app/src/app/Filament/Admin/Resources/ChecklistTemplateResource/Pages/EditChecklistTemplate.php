<?php

namespace App\Filament\Admin\Resources\ChecklistTemplateResource\Pages;

use App\Filament\Admin\Resources\ChecklistTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChecklistTemplate extends EditRecord
{
    protected static string $resource = ChecklistTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
