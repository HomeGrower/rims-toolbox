<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;

class RunScheduledBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled backup based on settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schedule = Setting::get('backup_schedule', 'disabled');
        
        if ($schedule === 'disabled') {
            $this->info('Scheduled backups are disabled.');
            return;
        }
        
        $now = now();
        $shouldRun = false;
        
        switch ($schedule) {
            case 'daily':
                $shouldRun = true;
                break;
            case 'weekly':
                $shouldRun = $now->dayOfWeek === 0; // Sunday
                break;
            case 'monthly':
                $shouldRun = $now->day === 1; // First day of month
                break;
        }
        
        if ($shouldRun) {
            $this->info('Running scheduled backup...');
            Artisan::call('backup:run', ['--only-db' => true]);
            $this->info('Scheduled backup completed.');
        } else {
            $this->info('No backup scheduled for today.');
        }
    }
}
