<?php

namespace App\Filament\Admin\Resources\ConditionResource\Pages;

use App\Filament\Admin\Resources\ConditionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCondition extends EditRecord
{
    protected static string $resource = ConditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}