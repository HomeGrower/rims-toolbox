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