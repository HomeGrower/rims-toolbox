<?php

namespace App\Filament\Admin\Resources\HotelBrandResource\Pages;

use App\Filament\Admin\Resources\HotelBrandResource;
use App\Filament\Admin\Resources\HotelChainResource;
use App\Models\HotelChain;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateHotelBrand extends CreateRecord
{
    protected static string $resource = HotelBrandResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If hotel_chain_id is in the URL parameters, use it
        if (request()->has('hotel_chain_id')) {
            $data['hotel_chain_id'] = request()->get('hotel_chain_id');
        }
        
        return $data;
    }
    
    public function mount(): void
    {
        parent::mount();
        
        // Pre-fill the hotel_chain_id if it's in the URL
        if (request()->has('hotel_chain_id')) {
            $this->form->fill([
                'hotel_chain_id' => request()->get('hotel_chain_id')
            ]);
        }
    }
    
    protected function afterCreate(): void
    {
        // Redirect back to the hotel chain page after creating
        $this->redirect(\App\Filament\Admin\Resources\HotelChainResource::getUrl('edit', [
            'record' => $this->record->hotel_chain_id
        ]));
    }
    
    public function getBreadcrumbs(): array
    {
        $resource = static::getResource();
        $chainId = request()->get('hotel_chain_id');
        
        if ($chainId) {
            $chain = HotelChain::find($chainId);
            if ($chain) {
                $breadcrumbs = [
                    HotelChainResource::getUrl() => HotelChainResource::getBreadcrumb(),
                    HotelChainResource::getUrl('edit', ['record' => $chain]) => $chain->name,
                    ...(filled($breadcrumb = $resource::getBreadcrumb()) ? [$breadcrumb] : []),
                    ...(filled($breadcrumb = $this->getBreadcrumb()) ? [$breadcrumb] : []),
                ];
                
                return $breadcrumbs;
            }
        }
        
        // Fallback to default breadcrumbs
        return parent::getBreadcrumbs();
    }
}
