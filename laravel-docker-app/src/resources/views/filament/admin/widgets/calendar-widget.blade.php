<x-filament-widgets::widget class="fi-wi-calendar">
    <x-filament::section collapsible collapsed=false>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <!-- Navigation buttons -->
                    <button 
                        wire:click="previousMonth"
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        title="Previous month"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    
                    <h2 class="text-xl font-semibold min-w-[200px] text-center">{{ $this->getCurrentMonth() }}</h2>
                    
                    <button 
                        wire:click="nextMonth"
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        title="Next month"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    
                    <button 
                        wire:click="goToToday"
                        class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                    >
                        Today
                    </button>
                </div>
            </div>
        </x-slot>

        <div class="calendar-container" 
             wire:loading.class="opacity-50 transition-opacity"
             wire:target="previousMonth,nextMonth,goToToday">
            <!-- Calendar Header -->
            <div class="grid grid-cols-7 gap-0 mb-2">
                @foreach($this->getWeekDays() as $day)
                    <div class="text-center font-semibold text-sm text-gray-600 dark:text-gray-400 py-2">
                        {{ $day }}
                    </div>
                @endforeach
            </div>

            <!-- Calendar Days -->
            <div class="grid grid-cols-7 gap-0 border-t border-l border-gray-200 dark:border-gray-700"
                 style="grid-auto-rows: minmax(120px, 1fr);">
                @forelse($this->getCalendarDays() as $dayData)
                    <div class="border-r border-b border-gray-200 dark:border-gray-700 p-2 relative overflow-hidden
                                {{ !$dayData['isCurrentMonth'] ? 'bg-gray-50 dark:bg-gray-900' : 'bg-white dark:bg-gray-800' }}
                                {{ $dayData['isToday'] ? 'bg-blue-50 dark:bg-blue-900/20 ring-2 ring-blue-500' : '' }}">
                        <div class="font-semibold text-sm mb-1 
                                    {{ !$dayData['isCurrentMonth'] ? 'text-gray-400 dark:text-gray-600' : 'text-gray-700 dark:text-gray-300' }}
                                    {{ $dayData['isToday'] ? 'text-primary-600 dark:text-primary-400' : '' }}">
                            {{ $dayData['day'] }}
                        </div>
                        
                        <div class="space-y-1">
                            @if($dayData['projects'] && count($dayData['projects']) > 0)
                                @foreach($dayData['projects'] as $project)
                                    <div class="text-xs p-1 rounded cursor-pointer hover:opacity-80 transition-opacity
                                                {{ $project['status'] === 'Demo' ? 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' : 'text-white' }}"
                                         style="{{ $project['status'] !== 'Demo' ? 'background-color: #F7A600;' : '' }}"
                                         title="{{ $project['title'] }} - {{ $project['hotel_chain'] }}/{{ $project['hotel_brand'] }} ({{ $project['access_code'] }})">
                                        <div class="truncate font-medium">{{ $project['title'] }}</div>
                                        <div class="truncate text-[10px] opacity-75">{{ $project['access_code'] }}</div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-7 text-center py-8 text-gray-500">
                        No calendar data available
                    </div>
                @endforelse
            </div>
        </div>

    </x-filament::section>
</x-filament-widgets::widget>