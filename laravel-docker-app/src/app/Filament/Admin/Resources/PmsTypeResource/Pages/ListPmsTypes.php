<?php

namespace App\Filament\Admin\Resources\PmsTypeResource\Pages;

use App\Filament\Admin\Resources\PmsTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPmsTypes extends ListRecords
{
    protected static string $resource = PmsTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
