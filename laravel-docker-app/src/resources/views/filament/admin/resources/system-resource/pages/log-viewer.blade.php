<x-filament-panels::page class="!max-w-none">
    <div class="space-y-4 -mx-6">
        <!-- Controls -->
        <div class="flex justify-between items-center px-6">
            <div class="flex gap-2 items-center">
                <x-filament::button
                    wire:click="loadLogs"
                    icon="heroicon-o-arrow-path"
                    size="sm"
                >
                    Refresh
                </x-filament::button>
                
                <x-filament::button
                    wire:click="toggleAutoRefresh"
                    :color="$autoRefresh ? 'success' : 'gray'"
                    icon="heroicon-o-clock"
                    size="sm"
                >
                    Auto-refresh: {{ $autoRefresh ? 'ON' : 'OFF' }}
                </x-filament::button>
                
                <x-filament::button
                    wire:click="clearLogs"
                    color="danger"
                    icon="heroicon-o-trash"
                    size="sm"
                    wire:confirm="Are you sure you want to clear all logs?"
                >
                    Clear Logs
                </x-filament::button>
                
                <div class="flex gap-2 items-center">
                    <label class="text-sm text-gray-600 dark:text-gray-400">Log file:</label>
                    <select 
                        wire:model.live="selectedLogFile"
                        wire:change="changeLogFile"
                        class="text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                    >
                        @foreach($availableLogFiles as $file)
                            <option value="{{ $file }}">{{ $file }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="flex gap-2 items-center">
                    <label class="text-sm text-gray-600 dark:text-gray-400">Lines:</label>
                    <select 
                        wire:model.live="lineCount"
                        wire:change="loadLogs"
                        class="text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                    >
                        <option value="100">100</option>
                        <option value="500">500</option>
                        <option value="1000">1000</option>
                        <option value="2000">2000</option>
                        <option value="5000">5000</option>
                        <option value="10000">10000</option>
                    </select>
                </div>
            </div>
            
            <div class="text-sm text-gray-500">
                Last updated: {{ now()->format('H:i:s') }}
            </div>
        </div>
        
        <!-- Log Display -->
        <div id="log-container" style="background-color: #111827; padding: 1rem; overflow: auto; height: calc(100vh - 300px); max-height: calc(100vh - 300px);">
            <pre style="color: #f3f4f6; font-size: 0.75rem; font-family: monospace; white-space: pre-wrap; margin: 0; line-height: 1.6;">{!! $logs !!}</pre>
        </div>
    </div>
    
    <!-- Auto-refresh Script -->
    <script>
        function scrollToBottom() {
            const logContainer = document.getElementById('log-container');
            if (logContainer) {
                logContainer.scrollTop = logContainer.scrollHeight;
            }
        }
        
        // Scroll to bottom on initial load
        document.addEventListener('DOMContentLoaded', scrollToBottom);
        
        // Scroll to bottom after Livewire updates
        document.addEventListener('livewire:morph.updated', (event) => {
            if (event.detail.el.id === 'log-container' || event.detail.el.querySelector('#log-container')) {
                setTimeout(scrollToBottom, 50);
            }
        });
        
        // Auto-refresh logic
        let refreshInterval;
        
        function startAutoRefresh() {
            clearInterval(refreshInterval);
            if (@json($autoRefresh)) {
                refreshInterval = setInterval(() => {
                    @this.call('refreshLogs');
                }, {{ $refreshInterval }});
            }
        }
        
        function stopAutoRefresh() {
            clearInterval(refreshInterval);
        }
        
        // Start auto-refresh on load
        document.addEventListener('DOMContentLoaded', () => {
            startAutoRefresh();
        });
        
        // Handle Livewire navigation
        document.addEventListener('livewire:navigated', () => {
            scrollToBottom();
            startAutoRefresh();
        });
        
        document.addEventListener('livewire:navigating', () => {
            stopAutoRefresh();
        });
        
        // Watch for auto-refresh toggle changes
        window.addEventListener('auto-refresh-toggled', (event) => {
            if (event.detail.enabled) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });
    </script>
    
    <style>
        /* Log viewer styling */
        pre {
            line-height: 1.6;
        }
        
        /* Highlight different log levels */
        .log-line {
            display: block;
            padding: 2px 0;
        }
        
        /* ERROR in red */
        pre:has-text("ERROR"),
        .log-line:has-text("ERROR") {
            color: #ef4444 !important;
        }
        
        /* WARNING in yellow */
        pre:has-text("WARNING"),
        .log-line:has-text("WARNING") {
            color: #f59e0b !important;
        }
        
        /* INFO in blue */
        pre:has-text("INFO"),
        .log-line:has-text("INFO") {
            color: #3b82f6 !important;
        }
    </style>
</x-filament-panels::page>
