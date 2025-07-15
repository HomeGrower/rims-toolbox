<x-filament-panels::page>
    @php
        $allTables = $masterTemplate['tables'] ?? [];
        $standardTables = \App\Models\Setting::get('standard_datastore_tables', ['properties', 'rooms', 'roomCategories', 'buildings', 'taxes', 'cancellationPolicies', 'colors', 'tagMapping']);
        $requiredTables = \App\Models\Setting::get('required_datastore_tables', ['properties', 'rooms', 'roomCategories']);
        
        // Get module tables
        $moduleTables = [];
        foreach ($record->modules as $module) {
            if (!empty($module->datastore_tables)) {
                foreach ($module->datastore_tables as $table) {
                    $moduleTables[$table] = $module->name;
                }
            }
        }
        
        // Get brand tables
        $brandTables = $record->hotelBrand ? ($record->hotelBrand->datastore_tables ?? []) : [];
        $customBrandTables = $record->hotelBrand ? ($record->hotelBrand->custom_datastore_tables ?? []) : [];
        
        // Get chain tables
        $customChainTables = $record->hotelChain ? ($record->hotelChain->custom_datastore_tables ?? []) : [];
    @endphp

    <div x-data="datastoreBuilder()" x-init="init()" class="space-y-6">
        <!-- Header Actions -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Datastore Configuration</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure which data tables are available for {{ $record->hotel_name }}</p>
            </div>
            <div class="flex gap-3">
                <x-filament::button color="gray" size="sm" @click="resetToDefault">
                    Reset to Default
                </x-filament::button>
                <x-filament::button color="info" size="sm" @click="importConfig">
                    Import
                </x-filament::button>
                <x-filament::button color="success" size="sm" @click="exportConfig">
                    Export JSON
                </x-filament::button>
                <x-filament::button size="sm" @click="saveConfiguration">
                    Save Configuration
                </x-filament::button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Categorized Tables -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center justify-between">
                        Available Tables
                        <span class="text-xs font-normal text-gray-500">
                            <span x-text="enabledCount"></span> enabled / <span x-text="totalCount"></span> total
                        </span>
                    </h3>
                    
                    <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                        <!-- Standard Tables -->
                        <div class="category-section">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 sticky top-0 bg-white dark:bg-gray-900 py-1">
                                📦 Standard Tables
                            </h4>
                            <div class="space-y-1">
                                @foreach($standardTables as $tableKey)
                                    @if(isset($allTables[$tableKey]))
                                        @php $table = $allTables[$tableKey]; @endphp
                                        <div class="table-item group" :class="getTableClasses('{{ $tableKey }}')">
                                            <div class="flex items-center justify-between p-2 rounded-lg transition-colors"
                                                 :class="isTableEnabled('{{ $tableKey }}') ? 'bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30' : 'bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700'">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-lg" x-text="getTableIcon('{{ $tableKey }}')"></span>
                                                    <span class="text-sm font-medium">{{ $table['label'] ?? ucfirst($tableKey) }}</span>
                                                </div>
                                                @if(!in_array($tableKey, $requiredTables))
                                                    <button @click="toggleTable('{{ $tableKey }}')"
                                                            class="px-2 py-1 text-xs rounded transition-colors"
                                                            :class="isTableEnabled('{{ $tableKey }}') ? 'bg-red-500 text-white hover:bg-red-600' : 'bg-green-500 text-white hover:bg-green-600'">
                                                        <span x-text="isTableEnabled('{{ $tableKey }}') ? 'Disable' : 'Enable'"></span>
                                                    </button>
                                                @else
                                                    <span class="text-xs text-gray-500">Required</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <!-- Module Tables -->
                        @if(count($moduleTables) > 0)
                            <div class="category-section">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 sticky top-0 bg-white dark:bg-gray-900 py-1">
                                    🔧 Module Tables
                                </h4>
                                <div class="space-y-1">
                                    @foreach($moduleTables as $tableKey => $moduleName)
                                        @if(isset($allTables[$tableKey]))
                                            @php $table = $allTables[$tableKey]; @endphp
                                            <div class="table-item group">
                                                <div class="flex items-center justify-between p-2 rounded-lg transition-colors"
                                                     :class="isTableEnabled('{{ $tableKey }}') ? 'bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30' : 'bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700'">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-lg" x-text="getTableIcon('{{ $tableKey }}')"></span>
                                                        <div>
                                                            <span class="text-sm font-medium">{{ $table['label'] ?? ucfirst($tableKey) }}</span>
                                                            <span class="text-xs text-gray-500 block">{{ $moduleName }}</span>
                                                        </div>
                                                    </div>
                                                    <button @click="toggleTable('{{ $tableKey }}')"
                                                            class="px-2 py-1 text-xs rounded transition-colors"
                                                            :class="isTableEnabled('{{ $tableKey }}') ? 'bg-red-500 text-white hover:bg-red-600' : 'bg-green-500 text-white hover:bg-green-600'">
                                                        <span x-text="isTableEnabled('{{ $tableKey }}') ? 'Disable' : 'Enable'"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Brand Tables -->
                        @if(count($brandTables) > 0 || count($customBrandTables) > 0)
                            <div class="category-section">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 sticky top-0 bg-white dark:bg-gray-900 py-1">
                                    🏢 Brand Tables ({{ $record->hotelBrand->name }})
                                </h4>
                                <div class="space-y-1">
                                    @foreach($brandTables as $tableKey)
                                        @if(isset($allTables[$tableKey]))
                                            @php $table = $allTables[$tableKey]; @endphp
                                            <div class="table-item group">
                                                <div class="flex items-center justify-between p-2 rounded-lg transition-colors bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-lg">🏢</span>
                                                        <span class="text-sm font-medium">{{ $table['label'] ?? ucfirst($tableKey) }}</span>
                                                    </div>
                                                    <button @click="toggleTable('{{ $tableKey }}')"
                                                            class="px-2 py-1 text-xs rounded transition-colors"
                                                            :class="isTableEnabled('{{ $tableKey }}') ? 'bg-red-500 text-white hover:bg-red-600' : 'bg-green-500 text-white hover:bg-green-600'">
                                                        <span x-text="isTableEnabled('{{ $tableKey }}') ? 'Disable' : 'Enable'"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                    
                                    @foreach($customBrandTables as $customTable)
                                        <div class="table-item group">
                                            <div class="flex items-center justify-between p-2 rounded-lg transition-colors bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-lg">⭐</span>
                                                    <div>
                                                        <span class="text-sm font-medium">{{ $customTable['label'] }}</span>
                                                        <span class="text-xs text-gray-500 block">Custom Table</span>
                                                    </div>
                                                </div>
                                                <span class="text-xs text-gray-500">Always Enabled</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Chain Tables -->
                        @if(count($customChainTables) > 0)
                            <div class="category-section">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 sticky top-0 bg-white dark:bg-gray-900 py-1">
                                    🏨 Chain Tables ({{ $record->hotelChain->name }})
                                </h4>
                                <div class="space-y-1">
                                    @foreach($customChainTables as $customTable)
                                        <div class="table-item group">
                                            <div class="flex items-center justify-between p-2 rounded-lg transition-colors bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-lg">⭐</span>
                                                    <div>
                                                        <span class="text-sm font-medium">{{ $customTable['label'] }}</span>
                                                        <span class="text-xs text-gray-500 block">Custom Table</span>
                                                    </div>
                                                </div>
                                                <span class="text-xs text-gray-500">Always Enabled</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Other Tables -->
                        @php
                            $categorizedTables = array_merge($standardTables, array_keys($moduleTables), $brandTables);
                            $otherTables = array_diff(array_keys($allTables), $categorizedTables);
                        @endphp
                        @if(count($otherTables) > 0)
                            <div class="category-section">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 sticky top-0 bg-white dark:bg-gray-900 py-1">
                                    📋 Other Tables
                                </h4>
                                <div class="space-y-1">
                                    @foreach($otherTables as $tableKey)
                                        @php $table = $allTables[$tableKey]; @endphp
                                        <div class="table-item group">
                                            <div class="flex items-center justify-between p-2 rounded-lg transition-colors"
                                                 :class="isTableEnabled('{{ $tableKey }}') ? 'bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30' : 'bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700'">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-lg" x-text="getTableIcon('{{ $tableKey }}')"></span>
                                                    <span class="text-sm font-medium">{{ $table['label'] ?? ucfirst($tableKey) }}</span>
                                                </div>
                                                <button @click="toggleTable('{{ $tableKey }}')"
                                                        class="px-2 py-1 text-xs rounded transition-colors"
                                                        :class="isTableEnabled('{{ $tableKey }}') ? 'bg-red-500 text-white hover:bg-red-600' : 'bg-green-500 text-white hover:bg-green-600'">
                                                    <span x-text="isTableEnabled('{{ $tableKey }}') ? 'Disable' : 'Enable'"></span>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Middle Column: Active Configuration -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Active Configuration</h3>
                    
                    <div class="space-y-2 max-h-[600px] overflow-y-auto pr-2">
                        <template x-for="tableKey in Object.keys(tables).sort((a, b) => {
                            const aEnabled = enabledTables.includes(a);
                            const bEnabled = enabledTables.includes(b);
                            if (aEnabled === bEnabled) return 0;
                            return aEnabled ? -1 : 1;
                        })" :key="tableKey">
                            <div @click="selectTable(tableKey)"
                                 class="p-3 rounded-lg border-2 cursor-pointer transition-all"
                                 :class="{
                                     'border-green-500 bg-green-50 dark:bg-green-900/20': enabledTables.includes(tableKey),
                                     'border-gray-300 bg-gray-50 dark:bg-gray-800 opacity-60': !enabledTables.includes(tableKey),
                                     'ring-2 ring-blue-500': selectedTable === tableKey
                                 }">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-lg" x-text="getTableIcon(tableKey)"></span>
                                            <strong class="text-sm" x-text="tables[tableKey].label || tableKey"></strong>
                                        </div>
                                        <small class="text-xs text-gray-500 mt-1 block">
                                            <span x-text="Object.keys(tables[tableKey].fields || {}).length"></span> fields
                                            <span x-show="tables[tableKey].description" class="ml-2" x-text="'• ' + tables[tableKey].description"></span>
                                        </small>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded"
                                          :class="enabledTables.includes(tableKey) ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400'">
                                        <span x-text="enabledTables.includes(tableKey) ? 'Enabled' : 'Disabled'"></span>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Right Column: Field Editor -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="p-6">
                    <template x-if="selectedTable && enabledTables.includes(selectedTable)">
                        <div>
                            <h3 class="text-lg font-semibold mb-4">
                                Edit: <span x-text="tables[selectedTable].label || selectedTable"></span>
                            </h3>
                            
                            <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
                                <template x-for="(field, fieldKey) in tables[selectedTable].fields || {}" :key="fieldKey">
                                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <div class="flex items-start justify-between mb-2">
                                            <div>
                                                <strong class="text-sm" x-text="field.label || fieldKey"></strong>
                                                <code class="text-xs text-gray-500 ml-2" x-text="fieldKey"></code>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 text-xs">
                                            <div>
                                                <span class="text-gray-500">Type:</span>
                                                <span class="font-medium ml-1" x-text="field.type || 'text'"></span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Required:</span>
                                                <span class="font-medium ml-1" x-text="field.required ? 'Yes' : 'No'"></span>
                                            </div>
                                            <div x-show="field.translate !== undefined">
                                                <span class="text-gray-500">Translatable:</span>
                                                <span class="font-medium ml-1" x-text="field.translate ? 'Yes' : 'No'"></span>
                                            </div>
                                        </div>
                                        <div x-show="field.description" class="mt-2 text-xs text-gray-600 dark:text-gray-400" x-text="field.description"></div>
                                    </div>
                                </template>
                                
                                <div x-show="!tables[selectedTable].fields || Object.keys(tables[selectedTable].fields).length === 0"
                                     class="text-center py-8 text-gray-500">
                                    No fields configured for this table
                                </div>
                            </div>
                        </div>
                    </template>
                    
                    <template x-if="selectedTable && !enabledTables.includes(selectedTable)">
                        <div class="text-center py-8">
                            <p class="text-gray-500 mb-4">This table is disabled</p>
                            <x-filament::button size="sm" color="success" @click="toggleTable(selectedTable)">
                                Enable Table
                            </x-filament::button>
                        </div>
                    </template>
                    
                    <div x-show="!selectedTable" class="text-center py-8 text-gray-500">
                        Select a table to view its configuration
                    </div>
                </div>
            </div>
        </div>

        <!-- JSON Preview Modal -->
        <div x-show="showPreview" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div @click="showPreview = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                
                <div class="relative bg-white dark:bg-gray-900 rounded-lg max-w-4xl w-full max-h-[80vh] overflow-hidden shadow-xl">
                    <div class="flex items-center justify-between p-4 border-b dark:border-gray-700">
                        <h3 class="text-lg font-semibold">Configuration Preview</h3>
                        <button @click="showPreview = false" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="p-4 overflow-y-auto max-h-[calc(80vh-8rem)]">
                        <pre class="text-sm bg-gray-100 dark:bg-gray-800 p-4 rounded-lg overflow-x-auto" x-text="jsonPreview"></pre>
                    </div>
                    <div class="flex justify-end p-4 border-t dark:border-gray-700">
                        <x-filament::button size="sm" color="gray" @click="showPreview = false">
                            Close
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function datastoreBuilder() {
            return {
                tables: @json($allTables),
                enabledTables: @json($allowedTables),
                requiredTables: @json($requiredTables),
                selectedTable: null,
                showPreview: false,
                jsonPreview: '',
                totalCount: 0,
                enabledCount: 0,
                
                init() {
                    this.totalCount = Object.keys(this.tables).length;
                    this.updateEnabledCount();
                    
                    // Add custom tables to the tables object
                    @foreach($customBrandTables as $customTable)
                        this.tables['{{ $customTable['name'] }}'] = {
                            label: '{{ $customTable['label'] }}',
                            icon: '{{ $customTable['icon'] ?? 'fa-table' }}',
                            description: '{{ $customTable['description'] ?? '' }}',
                            fields: @json($customTable['fields'] ?? []),
                            custom: true
                        };
                        this.enabledTables.push('{{ $customTable['name'] }}');
                    @endforeach
                    
                    @foreach($customChainTables as $customTable)
                        this.tables['{{ $customTable['name'] }}'] = {
                            label: '{{ $customTable['label'] }}',
                            icon: '{{ $customTable['icon'] ?? 'fa-table' }}',
                            description: '{{ $customTable['description'] ?? '' }}',
                            fields: @json($customTable['fields'] ?? []),
                            custom: true
                        };
                        this.enabledTables.push('{{ $customTable['name'] }}');
                    @endforeach
                    
                    this.totalCount = Object.keys(this.tables).length;
                    this.updateEnabledCount();
                },
                
                updateEnabledCount() {
                    this.enabledCount = this.enabledTables.length;
                },
                
                isTableEnabled(tableKey) {
                    return this.enabledTables.includes(tableKey);
                },
                
                getTableIcon(tableKey) {
                    if (this.requiredTables.includes(tableKey)) return '🔒';
                    if (!this.enabledTables.includes(tableKey)) return '❌';
                    if (this.tables[tableKey]?.custom) return '⭐';
                    return '✅';
                },
                
                getTableClasses(tableKey) {
                    return {
                        'opacity-50': !this.enabledTables.includes(tableKey),
                    };
                },
                
                toggleTable(tableKey) {
                    if (this.requiredTables.includes(tableKey)) {
                        this.$dispatch('notify', {
                            type: 'warning',
                            message: 'Required tables cannot be disabled'
                        });
                        return;
                    }
                    
                    const index = this.enabledTables.indexOf(tableKey);
                    if (index > -1) {
                        this.enabledTables.splice(index, 1);
                    } else {
                        this.enabledTables.push(tableKey);
                    }
                    
                    this.updateEnabledCount();
                },
                
                selectTable(tableKey) {
                    this.selectedTable = tableKey;
                },
                
                generateConfig() {
                    const config = {
                        tables: {},
                        languages: ['en']
                    };
                    
                    Object.entries(this.tables).forEach(([key, table]) => {
                        if (this.enabledTables.includes(key)) {
                            config.tables[key] = {
                                label: table.label,
                                fields: table.fields || {}
                            };
                            if (table.icon) config.tables[key].icon = table.icon;
                            if (table.description) config.tables[key].description = table.description;
                        } else {
                            config.tables[key] = { disabled: true };
                        }
                    });
                    
                    return config;
                },
                
                async saveConfiguration() {
                    try {
                        const response = await fetch('/api/projects/{{ $record->id }}/datastore-configuration', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                configuration: this.generateConfig()
                            })
                        });
                        
                        if (response.ok) {
                            window.$wireui.notify({
                                title: 'Success',
                                description: 'Configuration saved successfully',
                                icon: 'success'
                            });
                        } else {
                            throw new Error('Failed to save');
                        }
                    } catch (error) {
                        window.$wireui.notify({
                            title: 'Error',
                            description: 'Failed to save configuration',
                            icon: 'error'
                        });
                    }
                },
                
                resetToDefault() {
                    if (!confirm('Are you sure you want to reset to default configuration? This will remove all customizations.')) return;
                    window.location.reload();
                },
                
                importConfig() {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = '.json';
                    input.onchange = async (e) => {
                        const file = e.target.files[0];
                        if (!file) return;
                        
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            try {
                                const config = JSON.parse(event.target.result);
                                if (config.tables) {
                                    // Reset enabled tables
                                    this.enabledTables = [];
                                    
                                    // Apply configuration
                                    Object.entries(config.tables).forEach(([key, tableConfig]) => {
                                        if (!tableConfig.disabled) {
                                            this.enabledTables.push(key);
                                        }
                                    });
                                    
                                    this.updateEnabledCount();
                                    window.$wireui.notify({
                                        title: 'Success',
                                        description: 'Configuration imported successfully',
                                        icon: 'success'
                                    });
                                }
                            } catch (error) {
                                window.$wireui.notify({
                                    title: 'Error',
                                    description: 'Invalid JSON file',
                                    icon: 'error'
                                });
                            }
                        };
                        reader.readAsText(file);
                    };
                    input.click();
                },
                
                exportConfig() {
                    const config = this.generateConfig();
                    this.jsonPreview = JSON.stringify(config, null, 2);
                    this.showPreview = true;
                    
                    // Also download the file
                    const blob = new Blob([this.jsonPreview], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `datastore-config-{{ $record->id }}-${new Date().toISOString().split('T')[0]}.json`;
                    a.click();
                    URL.revokeObjectURL(url);
                }
            };
        }
    </script>
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-filament-panels::page>