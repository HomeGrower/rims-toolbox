<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h3 class="text-lg font-semibold mb-4">Datastore Configuration Status</h3>
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium mb-2">Project Information</h4>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Hotel:</dt>
                            <dd class="font-medium">{{ $record->hotel_name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Chain:</dt>
                            <dd class="font-medium">{{ $record->hotelChain?->name ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Brand:</dt>
                            <dd class="font-medium">{{ $record->hotelBrand?->name ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Modules:</dt>
                            <dd class="font-medium">{{ $record->modules->count() }}</dd>
                        </div>
                    </dl>
                </div>
                
                <div>
                    <h4 class="font-medium mb-2">Datastore Status</h4>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Total Tables:</dt>
                            <dd class="font-medium">{{ count($masterTemplate['tables'] ?? []) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Allowed Tables:</dt>
                            <dd class="font-medium text-green-600">{{ count($allowedTables) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Disabled Tables:</dt>
                            <dd class="font-medium text-red-600">{{ count($masterTemplate['tables'] ?? []) - count($allowedTables) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Configuration:</dt>
                            <dd class="font-medium">{{ $configuration->is_active ? 'Active' : 'Inactive' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h3 class="text-lg font-semibold mb-4">Table Overview</h3>
            
            <div class="grid grid-cols-3 gap-4">
                @php
                    $standardTables = ['properties', 'rooms', 'roomCategories', 'buildings', 'taxes', 'cancellationPolicies', 'colors', 'tagMapping'];
                    $requiredTables = ['properties', 'rooms', 'roomCategories'];
                @endphp
                
                <div>
                    <h4 class="font-medium mb-2 text-green-600">✅ Enabled Tables</h4>
                    <ul class="space-y-1 text-sm">
                        @foreach($allowedTables as $table)
                            <li class="flex items-center">
                                @if(in_array($table, $requiredTables))
                                    <span class="mr-1">🔒</span>
                                @else
                                    <span class="mr-1">✅</span>
                                @endif
                                {{ $masterTemplate['tables'][$table]['label'] ?? ucfirst($table) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-medium mb-2 text-gray-600">🚫 Disabled Tables</h4>
                    <ul class="space-y-1 text-sm text-gray-500">
                        @foreach($masterTemplate['tables'] ?? [] as $tableKey => $tableConfig)
                            @if(!in_array($tableKey, $allowedTables))
                                <li>❌ {{ $tableConfig['label'] ?? ucfirst($tableKey) }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-medium mb-2 text-blue-600">⭐ Custom Tables</h4>
                    <ul class="space-y-1 text-sm">
                        @if($record->hotelBrand && $record->hotelBrand->custom_datastore_tables)
                            @foreach($record->hotelBrand->custom_datastore_tables as $customTable)
                                <li>⭐ {{ $customTable['label'] ?? $customTable['name'] }}</li>
                            @endforeach
                        @endif
                        @if($record->hotelChain && $record->hotelChain->custom_datastore_tables)
                            @foreach($record->hotelChain->custom_datastore_tables as $customTable)
                                <li>⭐ {{ $customTable['label'] ?? $customTable['name'] }}</li>
                            @endforeach
                        @endif
                        @if(empty($record->hotelBrand->custom_datastore_tables) && empty($record->hotelChain->custom_datastore_tables))
                            <li class="text-gray-500">No custom tables</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Advanced Datastore Builder Coming Soon
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <p>The interactive datastore builder with drag-and-drop functionality is being developed. Current functionality:</p>
                        <ul class="list-disc list-inside mt-1">
                            <li>Automatic table detection based on modules, brand, and chain</li>
                            <li>Disabled tables are marked automatically</li>
                            <li>Configuration is saved and can be exported</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>