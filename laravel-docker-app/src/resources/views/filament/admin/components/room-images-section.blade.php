@props([
    'category' => '',
    'roomGroups' => [],
    'downloadAction' => ''
])

@php
    $totalImages = 0;
    foreach ($roomGroups as $roomGroup) {
        if ($roomGroup['main_image']) $totalImages++;
        $totalImages += count($roomGroup['slideshow_images']);
    }
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
    
    @if(empty($roomGroups))
        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No room images found.</p>
    @else
        <div class="space-y-6">
            @foreach($roomGroups as $roomGroup)
                <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                    {{-- Room Header --}}
                    <div class="mb-4">
                        <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100">
                            {{ $roomGroup['room_code'] }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $roomGroup['room_name'] }}
                        </p>
                    </div>
                    
                    {{-- Images Grid --}}
                    <div style="display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 0.5rem;">
                        {{-- Main Image --}}
                        @if($roomGroup['main_image'])
                            <div class="group relative">
                                <a href="{{ $roomGroup['main_image']['url'] }}" 
                                   download="{{ $roomGroup['main_image']['name'] }}"
                                   class="block aspect-square bg-gray-100 dark:bg-gray-900 rounded-lg overflow-hidden border-2 border-blue-500 dark:border-blue-400 cursor-pointer">
                                    <img src="{{ $roomGroup['main_image']['url'] }}" 
                                         alt="{{ $roomGroup['main_image']['display_name'] }}" 
                                         class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <span class="absolute bottom-2 left-2 right-2 bg-white/90 dark:bg-gray-800/90 text-xs font-medium text-center py-1.5 px-2 rounded block">
                                            Download
                                        </span>
                                    </div>
                                </a>
                                <p class="text-xs text-blue-600 dark:text-blue-400 text-center mt-1.5 font-medium truncate" 
                                   title="{{ $roomGroup['main_image']['display_name'] }}">
                                    {{ $roomGroup['main_image']['display_name'] }}
                                </p>
                            </div>
                        @endif
                        
                        {{-- Slideshow Images --}}
                        @foreach($roomGroup['slideshow_images'] as $slideshowImage)
                            <div class="group relative">
                                <a href="{{ $slideshowImage['url'] }}" 
                                   download="{{ $slideshowImage['name'] }}"
                                   class="block aspect-square bg-gray-100 dark:bg-gray-900 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 cursor-pointer">
                                    <img src="{{ $slideshowImage['url'] }}" 
                                         alt="{{ $slideshowImage['display_name'] }}" 
                                         class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <span class="absolute bottom-2 left-2 right-2 bg-white/90 dark:bg-gray-800/90 text-xs font-medium text-center py-1.5 px-2 rounded block">
                                            Download
                                        </span>
                                    </div>
                                </a>
                                <p class="text-xs text-gray-700 dark:text-gray-300 text-center mt-1.5 font-medium truncate" 
                                   title="{{ $slideshowImage['display_name'] }}">
                                    {{ $slideshowImage['display_name'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>