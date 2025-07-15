<x-filament-panels::page>
    @vite(['resources/js/datastore-builder.js'])
    
    <style>
        /* Disable scrolling on the entire page */
        html, body {
            overflow: hidden !important;
            height: 100vh !important;
            max-height: 100vh !important;
        }
        
        /* Remove default Filament page padding and prevent scrolling */
        .fi-page {
            overflow: hidden !important;
            height: 100vh !important;
        }
        
        .fi-page-wrapper {
            overflow: hidden !important;
            height: 100vh !important;
        }
        
        .fi-main {
            overflow: hidden !important;
        }
        
        .fi-main-ctn {
            overflow: hidden !important;
        }
        
        .fi-page-content {
            padding: 0 !important;
            overflow: hidden !important;
            display: flex !important;
            flex-direction: column !important;
            height: calc(85vh - 2rem) !important; /* 85% Höhe */
        }
        
        /* Make the app fill available space */
        #datastore-builder-app {
            flex: 1;
            overflow: hidden;
            position: relative;
            width: 100%;
            height: 100%;
            max-height: calc(85vh - 2rem);
        }
    </style>
    
    <div id="datastore-builder-app" 
         data-project-id="{{ $record->id }}"
         data-master-template='@json($masterTemplate)'
         data-default-structure='@json($defaultStructure)'
         data-configuration='@json($configuration->configuration ?? [])'
         data-project-modules='@json($projectModules)'
         data-chain-code='@json($chainCode)'
         data-allowed-tables='@json($allowedTables)'>
    </div>
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Listen for save event from Livewire
            Livewire.on('save-configuration', () => {
                window.dispatchEvent(new Event('save-configuration'));
            });
            
            // Listen for save event from Vue component
            window.addEventListener('save-datastore-configuration', (event) => {
                // Call Livewire component method
                @this.call('saveDatastoreConfiguration', event.detail.configuration);
            });
        });
    </script>
</x-filament-panels::page>