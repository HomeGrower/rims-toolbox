<?php

namespace App\Filament\Admin\Resources\HotelBrandResource\Pages;

use App\Filament\Admin\Resources\HotelBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHotelBrands extends ListRecords
{
    protected static string $resource = HotelBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
