<?php

namespace App\Filament\Admin\Resources\ChecklistTemplateResource\Pages;

use App\Filament\Admin\Resources\ChecklistTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChecklistTemplates extends ListRecords
{
    protected static string $resource = ChecklistTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
