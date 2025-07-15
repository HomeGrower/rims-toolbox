@php
    $progress = $getRecord()->overall_progress;
    
    $colorClass = match(true) {
        $progress >= 80 => 'bg-emerald-500',
        $progress >= 50 => 'bg-amber-500',
        default => 'bg-rose-500',
    };
    
    $ringColorClass = match(true) {
        $progress >= 80 => 'ring-emerald-500/20',
        $progress >= 50 => 'ring-amber-500/20',
        default => 'ring-rose-500/20',
    };
@endphp

<div class="flex items-center gap-3 min-w-[150px]">
    <div class="flex-1">
        <div class="relative">
            <!-- Background -->
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                <!-- Progress Bar -->
                <div class="{{ $colorClass }} h-2.5 rounded-full transition-all duration-500 ease-out relative overflow-hidden"
                     style="width: {{ $progress }}%">
                    <!-- Shimmer effect -->
                    <div class="absolute inset-0 -translate-x-full animate-[shimmer_2s_infinite]"
                         style="background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent)">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Percentage -->
    <span class="text-sm font-semibold tabular-nums text-gray-700 dark:text-gray-300 min-w-[45px] text-right">
        {{ $progress }}%
    </span>
</div>

<style>
    @keyframes shimmer {
        100% {
            transform: translateX(200%);
        }
    }
</style>