<div class="grid grid-cols-6 gap-2">
    @foreach($images as $image)
        <div class="group">
            <div class="relative aspect-square bg-gray-100 dark:bg-gray-900 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                <img src="{{ $image['url'] }}" alt="{{ $image['display_name'] }}" 
                     class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                    <a href="{{ $image['url'] }}" 
                       download="{{ $image['name'] }}"
                       class="absolute bottom-2 left-2 right-2 bg-white/90 dark:bg-gray-800/90 text-xs font-medium text-center py-1 px-2 rounded hover:bg-white dark:hover:bg-gray-800 transition-colors">
                        Download
                    </a>
                </div>
            </div>
            <p class="text-xs text-gray-700 dark:text-gray-300 text-center mt-1 font-medium truncate" title="{{ $image['display_name'] }}">
                {{ $image['display_name'] }}
            </p>
        </div>
    @endforeach
</div>