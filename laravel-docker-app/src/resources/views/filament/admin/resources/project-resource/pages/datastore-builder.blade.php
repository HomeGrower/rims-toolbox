<x-filament-panels::page>
    <div wire:ignore id="datastore-builder-wrapper" style="width: 100%; max-width: 100%;">
        <div id="datastore-builder-app" 
             data-project-id='{{ $project->id }}'
             data-master-template='@json($masterTemplate)'
             data-default-structure='@json($defaultStructure)'
             data-configuration='@json($configuration->toArray())'
             data-project-modules='@json($projectModules)'
             data-chain-code='@json($chainCode)'
             data-allowed-tables='@json($allowedTables)'
             style="min-height: 100vh; width: 100%;">
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/datastore-builder.js')
    @endpush
</x-filament-panels::page>
