<?php

namespace App\Filament\Admin\Resources\HotelChainResource\Pages;

use App\Filament\Admin\Resources\HotelChainResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHotelChains extends ListRecords
{
    protected static string $resource = HotelChainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
