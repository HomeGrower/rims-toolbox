<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Backup Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Backup Location</p>
                    <p class="font-medium">{{ storage_path('app/backup') }}</p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Backup Schedule</p>
                    <p class="font-medium">
                        @if($backupSchedule === 'disabled')
                            Manual backups only
                        @elseif($backupSchedule === 'daily')
                            Daily at 2:00 AM
                        @elseif($backupSchedule === 'weekly')
                            Weekly (Sunday at 2:00 AM)
                        @elseif($backupSchedule === 'monthly')
                            Monthly (1st day at 2:00 AM)
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Database</p>
                    <p class="font-medium">{{ config('database.default') }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6">
                <h3 class="text-xl font-semibold text-white flex items-center">
                    <svg class="h-6 w-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Database Upload & Restore
                </h3>
            </div>
            
            <form wire:submit.prevent="uploadDatabaseBackup" class="p-6">
                <div class="flex gap-6">
                    <!-- Left: Upload Section -->
                    <div class="flex-1">
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-xl p-6 border-2 border-dashed border-blue-300 dark:border-gray-600">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                
                                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Upload Database Backup</h3>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Select your SQL, ZIP or GZ backup file</p>
                                
                                <div class="mt-6">
                                    <label for="file-upload" class="relative cursor-pointer">
                                        <input id="file-upload" 
                                               type="file" 
                                               wire:model="uploadedBackup"
                                               accept=".sql,.zip,.gz"
                                               class="sr-only">
                                        <span class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                            </svg>
                                            Choose File
                                        </span>
                                    </label>
                                </div>
                                
                                @if($uploadedBackup)
                                    <div class="mt-4 inline-flex items-center px-4 py-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-green-800 dark:text-green-200">
                                            {{ $uploadedBackup->getClientOriginalName() }}
                                        </span>
                                        <span class="ml-2 text-xs text-green-600 dark:text-green-400">
                                            ({{ number_format($uploadedBackup->getSize() / 1024 / 1024, 2) }} MB)
                                        </span>
                                    </div>
                                @else
                                    <p class="mt-2 text-xs text-gray-500">Maximum file size: 500MB</p>
                                @endif
                            </div>
                        </div>
                        
                        @error('uploadedBackup') 
                            <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    <p class="text-sm text-red-800 dark:text-red-200">{{ $message }}</p>
                                </div>
                            </div>
                        @enderror
                    </div>
                    
                    <!-- Right: Warning Box -->
                    <div class="flex-1">
                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 h-full">
                            <div class="flex">
                                <svg class="h-5 w-5 text-amber-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-amber-800 dark:text-amber-200">Important Notice</h3>
                                    <ul class="mt-2 text-sm text-amber-700 dark:text-amber-300 list-disc list-inside space-y-1">
                                        <li>This will replace your entire database</li>
                                        <li>All existing data will be permanently deleted</li>
                                        <li>A backup of current data will be created first</li>
                                        <li>Admin user (admin@rims.live) will be preserved</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Spacer -->
                <div style="height: 24px;"></div>
                
                <!-- Upload Button - Centered below -->
                <div>
                    <div class="flex justify-center">
                    <button type="submit" 
                            @if(!$uploadedBackup) disabled @endif
                            wire:loading.attr="disabled"
                            style="@if($uploadedBackup) background-color: #10b981 !important; @else background-color: #9ca3af !important; @endif"
                            class="inline-flex items-center px-8 py-3 rounded-full text-white font-semibold text-base shadow-lg transition-all duration-200 disabled:cursor-not-allowed transform hover:scale-105">
                        <svg wire:loading class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg wire:loading.remove class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        <span wire:loading.remove>Upload and Restore Database</span>
                        <span wire:loading>Processing Backup...</span>
                    </button>
                    </div>
                </div>
                
                <div wire:loading wire:target="uploadDatabaseBackup" class="mt-4">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div class="flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-blue-700 dark:text-blue-300">Restoring database... This may take a few minutes.</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div>
            <h3 class="text-lg font-medium mb-4">Backup History</h3>
            
            @if(count($backups) > 0)
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Backup File
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Type
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Size
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Created
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($backups as $backup)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $backup['filename'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $backup['type'] === 'Full Backup' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ $backup['type'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $backup['size'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $backup['created_at'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button wire:click="downloadBackup('{{ $backup['path'] }}')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                            Download
                                        </button>
                                        @if($backup['type'] === 'Database Only')
                                            <button wire:click="restoreBackup('{{ $backup['path'] }}')" wire:confirm="Are you sure you want to restore this backup? This will replace all current data!" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 mr-3">
                                                Restore
                                            </button>
                                        @endif
                                        <button wire:click="deleteBackup('{{ $backup['path'] }}')" wire:confirm="Are you sure you want to delete this backup?" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No backups found. Create your first backup using the buttons above.
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>