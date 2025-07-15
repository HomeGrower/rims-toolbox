<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl" style="height: 85vh; overflow: hidden;">
        <div id="datastore-builder-toolbox-app"
             data-master-template='@json($this->masterTemplate)'
             data-default-structure='@json($this->defaultStructure)'
             style="height: 100%;">
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/datastore-builder-toolbox.js')
    @endpush
    
    @push('styles')
        <style>
            /* Disable page scrolling when datastore builder is active */
            body {
                overflow: hidden !important;
            }
            .fi-page {
                height: 100%;
                overflow: hidden;
            }
        </style>
    @endpush
</x-filament-panels::page>