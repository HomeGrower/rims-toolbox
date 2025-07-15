<?php

namespace App\Filament\Admin\Resources\ProjectResource\Pages;

use App\Filament\Admin\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Quick Create'),
            Actions\Action::make('createWithWizard')
                ->label('Create with Wizard')
                ->icon('heroicon-o-sparkles')
                ->url(fn (): string => ProjectResource::getUrl('create-wizard'))
                ->color('success'),
        ];
    }
}
