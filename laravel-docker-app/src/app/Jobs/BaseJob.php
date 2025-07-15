<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Middleware\ReleaseMemory;

abstract class BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new ReleaseMemory];
    }

    /**
     * Clear any heavy resources after job completion.
     */
    protected function cleanup(): void
    {
        // Clear any cached data
        gc_collect_cycles();
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        // Log the failure
        \Log::error('Job failed: ' . get_class($this), [
            'exception' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString()
        ]);
    }
}