<?php

namespace App\Jobs\Middleware;

use Closure;

class ReleaseMemory
{
    /**
     * Process the queued job.
     *
     * @param  \Closure(object): void  $next
     */
    public function handle(object $job, Closure $next): void
    {
        $next($job);
        
        // Clear Eloquent model cache
        if (method_exists($job, 'clearResolvedInstances')) {
            $job->clearResolvedInstances();
        }
        
        // Force garbage collection
        gc_collect_cycles();
    }
}