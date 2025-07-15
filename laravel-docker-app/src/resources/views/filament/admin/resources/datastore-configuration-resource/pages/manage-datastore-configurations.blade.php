<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="save">
            {{ $this->form }}

            <x-filament::button type="submit" class="mt-6">
                Save Changes
            </x-filament::button>
        </form>
        
        <div>
            <h2 class="text-lg font-medium mb-4">Version History</h2>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>