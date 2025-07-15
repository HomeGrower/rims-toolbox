<?php

namespace App\Filament\Admin\Resources\HotelChainResource\Pages;

use App\Filament\Admin\Resources\HotelChainResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHotelChain extends EditRecord
{
    protected static string $resource = HotelChainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
