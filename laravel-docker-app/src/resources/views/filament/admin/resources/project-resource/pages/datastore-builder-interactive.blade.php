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

    <div x-data="datastoreBuilder()" x-init="init()" class="h-full">
        <!-- Header Actions -->
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold">Datastore Configuration</h2>
            <div class="flex gap-2">
                <x-filament::button color="gray" size="sm" @click="resetToDefault">Reset</x-filament::button>
                <x-filament::button color="info" size="sm" @click="importConfig">Import</x-filament::button>
                <x-filament::button color="success" size="sm" @click="exportConfig">Export</x-filament::button>
                <x-filament::button size="sm" @click="saveConfiguration">Save</x-filament::button>
            </div>
        </div>

        <!-- Three Column Layout -->
        <div class="grid grid-cols-3 gap-4 h-[calc(100vh-300px)]">
            <!-- Left Column: Available Tables -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden flex flex-col">
                <div class="p-4 border-b dark:border-gray-700">
                    <h3 class="font-semibold">Available Tables</h3>
                </div>
                <div class="flex-1 overflow-y-auto p-4">
                    <!-- Standard Tables -->
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">📦 Standard Tables</h4>
                        <div class="space-y-1">
                            @foreach($standardTables as $tableKey)
                                @if(isset($allTables[$tableKey]) && !in_array($tableKey, $allowedTables))
                                    <div draggable="true" 
                                         @dragstart="handleDragStart($event, '{{ $tableKey }}')"
                                         @dragend="handleDragEnd"
                                         class="p-2 bg-gray-100 dark:bg-gray-800 rounded cursor-move hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                                         :class="{ 'opacity-50': dragging === '{{ $tableKey }}' }">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm">{{ $allTables[$tableKey]['label'] ?? ucfirst($tableKey) }}</span>
                                            <button @click="enableTable('{{ $tableKey }}')" 
                                                    class="text-xs px-2 py-1 bg-green-500 text-white rounded hover:bg-green-600">
                                                Enable
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <!-- Module Tables -->
                    @if(count($moduleTables) > 0)
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">🔧 Module Tables</h4>
                            <div class="space-y-1">
                                @foreach($moduleTables as $tableKey => $moduleName)
                                    @if(isset($allTables[$tableKey]) && !in_array($tableKey, $allowedTables))
                                        <div draggable="true"
                                             @dragstart="handleDragStart($event, '{{ $tableKey }}')"
                                             @dragend="handleDragEnd"
                                             class="p-2 bg-blue-100 dark:bg-blue-900/20 rounded cursor-move hover:bg-blue-200 dark:hover:bg-blue-900/30 transition-colors"
                                             :class="{ 'opacity-50': dragging === '{{ $tableKey }}' }">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <span class="text-sm">{{ $allTables[$tableKey]['label'] ?? ucfirst($tableKey) }}</span>
                                                    <span class="text-xs text-gray-500 block">{{ $moduleName }}</span>
                                                </div>
                                                <button @click="enableTable('{{ $tableKey }}')"
                                                        class="text-xs px-2 py-1 bg-green-500 text-white rounded hover:bg-green-600">
                                                    Enable
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Other Disabled Tables -->
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">🚫 Disabled Tables</h4>
                        <div class="space-y-1">
                            @foreach($allTables as $tableKey => $table)
                                @if(!in_array($tableKey, $allowedTables) && !in_array($tableKey, $standardTables) && !isset($moduleTables[$tableKey]))
                                    <div draggable="true"
                                         @dragstart="handleDragStart($event, '{{ $tableKey }}')"
                                         @dragend="handleDragEnd"
                                         class="p-2 bg-red-100 dark:bg-red-900/20 rounded cursor-move hover:bg-red-200 dark:hover:bg-red-900/30 transition-colors"
                                         :class="{ 'opacity-50': dragging === '{{ $tableKey }}' }">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm">{{ $table['label'] ?? ucfirst($tableKey) }}</span>
                                            <button @click="enableTable('{{ $tableKey }}')"
                                                    class="text-xs px-2 py-1 bg-green-500 text-white rounded hover:bg-green-600">
                                                Enable
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Middle Column: Active Configuration -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden flex flex-col"
                 @drop="handleDrop($event)"
                 @dragover.prevent
                 @dragenter.prevent>
                <div class="p-4 border-b dark:border-gray-700">
                    <h3 class="font-semibold">Active Configuration</h3>
                </div>
                <div class="flex-1 overflow-y-auto p-4">
                    <div class="space-y-2">
                        <!-- Required Tables -->
                        @foreach($requiredTables as $tableKey)
                            @if(isset($allTables[$tableKey]))
                                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded border-2 border-green-500">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="mr-2">🔒</span>
                                            <div>
                                                <strong class="text-sm">{{ $allTables[$tableKey]['label'] ?? ucfirst($tableKey) }}</strong>
                                                <small class="block text-xs text-gray-500">Required - Cannot be disabled</small>
                                            </div>
                                        </div>
                                        <button @click="selectTable('{{ $tableKey }}')"
                                                class="text-xs px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                                            Configure
                                        </button>
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        <!-- Enabled Tables -->
                        <template x-for="tableKey in enabledTables" :key="tableKey">
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded border-2 border-green-500 cursor-pointer hover:border-green-600"
                                 @click="selectTable(tableKey)">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <span class="mr-2 cursor-move">⋮⋮</span>
                                        <div>
                                            <strong class="text-sm" x-text="tables[tableKey]?.label || tableKey"></strong>
                                            <small class="block text-xs text-gray-500">
                                                <span x-text="Object.keys(tables[tableKey]?.fields || {}).length"></span> fields
                                            </small>
                                        </div>
                                    </div>
                                    <button @click.stop="disableTable(tableKey)"
                                            class="text-xs px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600">
                                        Disable
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- Custom Tables -->
                        @foreach($customBrandTables as $customTable)
                            <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded border-2 border-purple-500">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <span class="mr-2">⭐</span>
                                        <div>
                                            <strong class="text-sm">{{ $customTable['label'] }}</strong>
                                            <small class="block text-xs text-gray-500">Brand Custom Table</small>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-500">Always Enabled</span>
                                </div>
                            </div>
                        @endforeach

                        @foreach($customChainTables as $customTable)
                            <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded border-2 border-purple-500">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <span class="mr-2">⭐</span>
                                        <div>
                                            <strong class="text-sm">{{ $customTable['label'] }}</strong>
                                            <small class="block text-xs text-gray-500">Chain Custom Table</small>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-500">Always Enabled</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Drop Zone -->
                    <div x-show="dragging" 
                         class="mt-4 p-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-center">
                        <p class="text-gray-500">Drop table here to enable</p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Field Editor -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden flex flex-col">
                <div class="p-4 border-b dark:border-gray-700">
                    <h3 class="font-semibold">Field Configuration</h3>
                </div>
                <div class="flex-1 overflow-y-auto p-4">
                    <template x-if="selectedTable">
                        <div>
                            <h4 class="font-medium mb-4" x-text="'Configure: ' + (tables[selectedTable]?.label || selectedTable)"></h4>
                            
                            <div class="space-y-3">
                                <template x-for="(field, fieldKey) in tables[selectedTable]?.fields || {}" :key="fieldKey">
                                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded">
                                        <div class="flex items-start justify-between mb-2">
                                            <div>
                                                <strong class="text-sm" x-text="field.label || fieldKey"></strong>
                                                <code class="text-xs text-gray-500 ml-2" x-text="fieldKey"></code>
                                            </div>
                                            <button @click="removeField(selectedTable, fieldKey)"
                                                    class="text-xs text-red-600 hover:text-red-800">
                                                Remove
                                            </button>
                                        </div>
                                        
                                        <div class="space-y-2">
                                            <div class="grid grid-cols-2 gap-2">
                                                <label class="text-xs">
                                                    <input type="checkbox" x-model="field.required" class="mr-1">
                                                    Required
                                                </label>
                                                <label class="text-xs">
                                                    <input type="checkbox" x-model="field.translate" class="mr-1">
                                                    Translatable
                                                </label>
                                            </div>
                                            
                                            <input type="text" x-model="field.label" 
                                                   placeholder="Field Label"
                                                   class="w-full text-sm px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600">
                                            
                                            <select x-model="field.type"
                                                    class="w-full text-sm px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600">
                                                <option value="text">Text</option>
                                                <option value="textarea">Textarea</option>
                                                <option value="number">Number</option>
                                                <option value="select">Select</option>
                                                <option value="boolean">Boolean</option>
                                                <option value="date">Date</option>
                                                <option value="image">Image</option>
                                                <option value="summernote">Rich Text</option>
                                            </select>
                                        </div>
                                    </div>
                                </template>
                                
                                <button @click="addField(selectedTable)"
                                        class="w-full py-2 bg-blue-500 text-white text-sm rounded hover:bg-blue-600">
                                    + Add Field
                                </button>
                            </div>
                        </div>
                    </template>
                    
                    <div x-show="!selectedTable" class="text-center py-8 text-gray-500">
                        Select a table to configure its fields
                    </div>
                </div>
            </div>
        </div>

        <!-- JSON Preview (Bottom) -->
        <div class="mt-4">
            <button @click="showPreview = !showPreview" 
                    class="text-sm text-blue-600 hover:text-blue-800">
                <span x-text="showPreview ? 'Hide' : 'Show'"></span> JSON Preview
            </button>
            
            <div x-show="showPreview" x-collapse class="mt-2">
                <pre class="bg-gray-900 text-white p-4 rounded text-xs overflow-x-auto max-h-48" x-text="getJsonPreview()"></pre>
            </div>
        </div>
    </div>

    <script>
        function datastoreBuilder() {
            return {
                tables: @json($allTables),
                enabledTables: @json(array_diff($allowedTables, $requiredTables)),
                requiredTables: @json($requiredTables),
                selectedTable: null,
                dragging: null,
                showPreview: false,
                
                init() {
                    // Add custom tables
                    @foreach($customBrandTables as $customTable)
                        this.tables['{{ $customTable['name'] }}'] = @json($customTable);
                    @endforeach
                    
                    @foreach($customChainTables as $customTable)
                        this.tables['{{ $customTable['name'] }}'] = @json($customTable);
                    @endforeach
                },
                
                handleDragStart(event, tableKey) {
                    this.dragging = tableKey;
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', tableKey);
                },
                
                handleDragEnd() {
                    this.dragging = null;
                },
                
                handleDrop(event) {
                    event.preventDefault();
                    const tableKey = event.dataTransfer.getData('text/plain');
                    this.enableTable(tableKey);
                    this.dragging = null;
                },
                
                enableTable(tableKey) {
                    if (!this.enabledTables.includes(tableKey)) {
                        this.enabledTables.push(tableKey);
                    }
                },
                
                disableTable(tableKey) {
                    const index = this.enabledTables.indexOf(tableKey);
                    if (index > -1) {
                        this.enabledTables.splice(index, 1);
                    }
                    if (this.selectedTable === tableKey) {
                        this.selectedTable = null;
                    }
                },
                
                selectTable(tableKey) {
                    this.selectedTable = tableKey;
                },
                
                addField(tableKey) {
                    if (!this.tables[tableKey].fields) {
                        this.tables[tableKey].fields = {};
                    }
                    const fieldKey = 'field_' + Date.now();
                    this.tables[tableKey].fields[fieldKey] = {
                        type: 'text',
                        label: 'New Field',
                        required: false,
                        translate: false
                    };
                },
                
                removeField(tableKey, fieldKey) {
                    delete this.tables[tableKey].fields[fieldKey];
                },
                
                getJsonPreview() {
                    const config = {
                        tables: {},
                        languages: ['en']
                    };
                    
                    // Add all tables
                    [...this.requiredTables, ...this.enabledTables].forEach(key => {
                        if (this.tables[key]) {
                            config.tables[key] = this.tables[key];
                        }
                    });
                    
                    // Add disabled tables
                    Object.keys(this.tables).forEach(key => {
                        if (!config.tables[key]) {
                            config.tables[key] = { disabled: true };
                        }
                    });
                    
                    return JSON.stringify(config, null, 2);
                },
                
                async saveConfiguration() {
                    // Implementation for saving
                    alert('Configuration saved!');
                },
                
                resetToDefault() {
                    if (confirm('Reset to default configuration?')) {
                        window.location.reload();
                    }
                },
                
                importConfig() {
                    // Implementation for import
                    alert('Import functionality');
                },
                
                exportConfig() {
                    const config = this.getJsonPreview();
                    const blob = new Blob([config], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'datastore-config.json';
                    a.click();
                }
            };
        }
    </script>
</x-filament-panels::page>