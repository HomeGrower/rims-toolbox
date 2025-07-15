<div>
    <x-filament-panels::page>
        @if($this->record->hotelBrand?->logo_url)
            <div class="absolute top-6 left-6">
                <img src="{{ \Illuminate\Support\Facades\Storage::url($this->record->hotelBrand->logo_url) }}" 
                     alt="{{ $this->record->hotelBrand->name }}" 
                     class="h-12 w-auto object-contain" />
            </div>
        @endif
    </x-filament-panels::page>
</div>