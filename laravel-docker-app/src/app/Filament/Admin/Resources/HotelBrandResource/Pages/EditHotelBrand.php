<?php

namespace App\Filament\Admin\Resources\HotelBrandResource\Pages;

use App\Filament\Admin\Resources\HotelBrandResource;
use App\Filament\Admin\Resources\HotelChainResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Navigation\NavigationItem;
use Illuminate\Contracts\Support\Htmlable;

class EditHotelBrand extends EditRecord
{
    protected static string $resource = HotelBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        // Redirect back to the hotel chain page after saving
        return \App\Filament\Admin\Resources\HotelChainResource::getUrl('edit', [
            'record' => $this->record->hotel_chain_id
        ]);
    }
    
    public function getBreadcrumbs(): array
    {
        $resource = static::getResource();
        $chain = $this->record->hotelChain;

        $breadcrumbs = [
            HotelChainResource::getUrl() => HotelChainResource::getBreadcrumb(),
            HotelChainResource::getUrl('edit', ['record' => $chain]) => $chain->name,
            ...(filled($breadcrumb = $resource::getBreadcrumb()) ? [$breadcrumb] : []),
            ...(filled($breadcrumb = $this->getBreadcrumb()) ? [$breadcrumb] : []),
        ];

        return $breadcrumbs;
    }
}
