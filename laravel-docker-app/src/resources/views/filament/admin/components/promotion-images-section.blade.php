@props([
    'category' => '',
    'promotionGroups' => [],
    'downloadAction' => ''
])

@php
    $totalImages = count($promotionGroups);
@endphp

<div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ $category }} <span class="text-sm text-gray-500 dark:text-gray-400">({{ $totalImages }} images)</span>
        </h3>
        
        <button type="button" 
                wire:click="{{ $downloadAction }}" 
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
            </svg>
            Download All as ZIP
        </button>
    </div>
    
    @if(empty($promotionGroups))
        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No promotion images found.</p>
    @else
        <div class="space-y-6">
            @foreach($promotionGroups as $promotionGroup)
                <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                    {{-- Promotion Header --}}
                    <div class="mb-4">
                        <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100">
                            {{ $promotionGroup['name'] }}
                        </h4>
                        <div class="flex gap-4 text-sm text-gray-600 dark:text-gray-400">
                            @if(isset($promotionGroup['promotion_type']))
                                <span>Type: {{ $promotionGroup['promotion_type'] }}</span>
                            @endif
                            <span>Priority: {{ $promotionGroup['priority'] }}</span>
                        </div>
                    </div>
                    
                    {{-- Promotion Image --}}
                    <div style="display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 0.5rem;">
                        @if($promotionGroup['image'])
                            <div class="group relative">
                                <a href="{{ $promotionGroup['image']['url'] }}" 
                                   download="{{ $promotionGroup['image']['name'] }}"
                                   class="block aspect-square bg-gray-100 dark:bg-gray-900 rounded-lg overflow-hidden border-2 border-purple-500 dark:border-purple-400 cursor-pointer">
                                    <img src="{{ $promotionGroup['image']['url'] }}" 
                                         alt="{{ $promotionGroup['name'] }}" 
                                         class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <span class="absolute bottom-2 left-2 right-2 bg-white/90 dark:bg-gray-800/90 text-xs font-medium text-center py-1.5 px-2 rounded block">
                                            Download
                                        </span>
                                    </div>
                                </a>
                                <p class="text-xs text-purple-600 dark:text-purple-400 text-center mt-1.5 font-medium truncate" 
                                   title="{{ $promotionGroup['image']['display_name'] }}">
                                    {{ $promotionGroup['image']['display_name'] }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>