<?php

namespace App\Filament\Admin\Resources\SystemResource\Pages;

use App\Filament\Admin\Resources\SystemResource;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use Livewire\WithFileUploads;

class BackupManager extends Page implements HasForms
{
    use InteractsWithForms, WithFileUploads;

    protected static string $resource = SystemResource::class;

    protected static string $view = 'filament.admin.resources.system-resource.pages.backup-manager';
    
    protected static ?string $title = 'Backup Manager';
    
    protected static ?string $navigationLabel = 'Backups';
    
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    
    public $isRunning = false;
    public $backups = [];
    public $backupSchedule;
    public $uploadedBackup;
    
    public function mount(): void
    {
        $this->loadBackups();
        $this->backupSchedule = Setting::get('backup_schedule', 'disabled');
    }
    
    public function loadBackups(): void
    {
        $disk = Storage::disk('backup');
        
        $files = $disk->allFiles('/');
        
        $this->backups = collect($files)
            ->filter(function ($file) {
                return str_ends_with($file, '.zip');
            })
            ->map(function ($file) use ($disk) {
                // Determine backup type by checking the contents
                $type = 'Full Backup';
                try {
                    $zip = new ZipArchive();
                    $fullPath = storage_path('app/backup/' . $file);
                    if ($zip->open($fullPath) === true) {
                        $hasDbDump = false;
                        $hasOtherFiles = false;
                        
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $filename = $zip->getNameIndex($i);
                            if (str_starts_with($filename, 'db-dumps/')) {
                                $hasDbDump = true;
                            } else {
                                $hasOtherFiles = true;
                            }
                        }
                        $zip->close();
                        
                        if ($hasDbDump && !$hasOtherFiles) {
                            $type = 'Database Only';
                        } elseif (!$hasDbDump && $hasOtherFiles) {
                            $type = 'Files Only';
                        }
                    }
                } catch (\Exception $e) {
                    // Default to Full Backup if we can't determine
                }
                
                return [
                    'id' => md5($file),
                    'filename' => basename($file),
                    'path' => $file,
                    'size' => $this->formatBytes($disk->size($file)),
                    'created_at' => Carbon::createFromTimestamp($disk->lastModified($file))->format('Y-m-d H:i:s'),
                    'type' => $type,
                ];
            })
            ->sortByDesc('created_at')
            ->values()
            ->toArray();
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('backup')
                ->label('Create Backup')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Select::make('backup_type')
                        ->label('Backup Type')
                        ->options([
                            'database' => 'Database Only',
                            'files' => 'Files Only',
                            'full' => 'Full Backup (Database + Files)',
                        ])
                        ->default('database')
                        ->required(),
                    Toggle::make('include_logs')
                        ->label('Include Log Files')
                        ->default(false)
                        ->visible(fn ($get) => in_array($get('backup_type'), ['files', 'full'])),
                    Toggle::make('include_temp')
                        ->label('Include Temporary Files')
                        ->default(false)
                        ->visible(fn ($get) => in_array($get('backup_type'), ['files', 'full'])),
                ])
                ->action(function (array $data) {
                    try {
                        $options = [];
                        
                        if ($data['backup_type'] === 'database') {
                            $options['--only-db'] = true;
                        } elseif ($data['backup_type'] === 'files') {
                            $options['--only-files'] = true;
                        }
                        
                        if (!($data['include_logs'] ?? false)) {
                            // Exclude logs if not included
                            config(['backup.backup.source.files.exclude' => array_merge(
                                config('backup.backup.source.files.exclude', []),
                                [storage_path('logs')]
                            )]);
                        }
                        
                        if (!($data['include_temp'] ?? false)) {
                            // Exclude temp files if not included
                            config(['backup.backup.source.files.exclude' => array_merge(
                                config('backup.backup.source.files.exclude', []),
                                [storage_path('app/temp'), storage_path('framework/cache')]
                            )]);
                        }
                        
                        Artisan::call('backup:run', $options);
                        
                        Notification::make()
                            ->title('Backup created successfully')
                            ->success()
                            ->send();
                            
                        $this->loadBackups();
                        $this->dispatch('$refresh');
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Backup failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Action::make('schedule')
                ->label('Schedule Settings')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->form([
                    Select::make('schedule')
                        ->label('Backup Schedule')
                        ->options([
                            'disabled' => 'Disabled',
                            'daily' => 'Daily at 2:00 AM',
                            'weekly' => 'Weekly (Sunday at 2:00 AM)',
                            'monthly' => 'Monthly (1st day at 2:00 AM)',
                        ])
                        ->default($this->backupSchedule)
                        ->helperText('Automatic backups will be created according to this schedule'),
                ])
                ->action(function (array $data) {
                    Setting::set('backup_schedule', $data['schedule']);
                    $this->backupSchedule = $data['schedule'];
                    
                    Notification::make()
                        ->title('Schedule updated')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    public function downloadBackup(string $path): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return Storage::disk('backup')->download($path);
    }
    
    public function deleteBackup(string $path): void
    {
        Storage::disk('backup')->delete($path);
        
        Notification::make()
            ->title('Backup deleted')
            ->success()
            ->send();
            
        $this->loadBackups();
    }
    
    public function restoreBackup(string $path): void
    {
        try {
            $disk = Storage::disk('backup');
            $backupPath = storage_path('app/backup/' . $path);
            
            // Determine backup type by checking contents
            $isDbOnly = false;
            $zip = new ZipArchive();
            if ($zip->open($backupPath) === true) {
                $hasDbDump = false;
                $hasOtherFiles = false;
                
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    if (str_starts_with($filename, 'db-dumps/')) {
                        $hasDbDump = true;
                    } else {
                        $hasOtherFiles = true;
                    }
                }
                $zip->close();
                
                $isDbOnly = $hasDbDump && !$hasOtherFiles;
            }
            
            if ($isDbOnly) {
                // Database only restore
                $this->restoreDatabase($backupPath);
            } else {
                // Full restore
                Notification::make()
                    ->title('Full restore not implemented')
                    ->body('Full restore functionality is not yet implemented for safety reasons.')
                    ->warning()
                    ->send();
                return;
            }
            
            Notification::make()
                ->title('Backup restored successfully')
                ->body('The database has been restored from the backup.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Restore failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    private function restoreDatabase(string $backupPath): void
    {
        // Extract the SQL file from the zip
        $zip = new ZipArchive();
        if ($zip->open($backupPath) === true) {
            $tempDir = storage_path('app/temp/restore_' . time());
            mkdir($tempDir, 0755, true);
            
            $zip->extractTo($tempDir);
            $zip->close();
            
            // Find the SQL file
            $files = glob($tempDir . '/db-dumps/*.sql');
            if (empty($files)) {
                throw new \Exception('No SQL file found in backup');
            }
            
            $sqlFile = $files[0];
            
            // Get database credentials
            $dbHost = config('database.connections.mysql.host');
            $dbPort = config('database.connections.mysql.port');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            
            // Restore the database
            $command = sprintf(
                'mysql -h %s -P %s -u %s -p%s %s < %s',
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($sqlFile)
            );
            
            exec($command . ' 2>&1', $output, $returnCode);
            
            // Clean up temp files
            array_map('unlink', glob($tempDir . '/*'));
            rmdir($tempDir);
            
            if ($returnCode !== 0) {
                throw new \Exception('Database restore failed: ' . implode("\n", $output));
            }
        } else {
            throw new \Exception('Could not open backup file');
        }
    }
    
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
    
    public function uploadDatabaseBackup(): void
    {
        $this->validate([
            'uploadedBackup' => 'required|file|mimes:sql,zip|max:512000', // Max 500MB
        ]);
        
        try {
            $file = $this->uploadedBackup;
            $tempPath = $file->getRealPath();
            $extension = $file->getClientOriginalExtension();
            
            if ($extension === 'sql') {
                // Direct SQL file
                $this->restoreDatabaseFromSql($tempPath);
            } elseif ($extension === 'zip') {
                // ZIP file containing SQL
                $this->restoreDatabaseFromZip($tempPath);
            }
            
            Notification::make()
                ->title('Database restored successfully')
                ->body('The database has been restored from the uploaded backup.')
                ->success()
                ->send();
                
            // Clear the upload
            $this->uploadedBackup = null;
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Upload failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    private function restoreDatabaseFromSql(string $sqlPath): void
    {
        // Get database credentials
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        
        // Restore the database
        $command = sprintf(
            'mysql -h %s -P %s -u %s -p%s %s < %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($sqlPath)
        );
        
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception('Database restore failed: ' . implode("\n", $output));
        }
        
        // Ensure admin user exists after restore
        $this->ensureAdminUser();
    }
    
    private function restoreDatabaseFromZip(string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $tempDir = storage_path('app/temp/upload_restore_' . time());
            mkdir($tempDir, 0755, true);
            
            $zip->extractTo($tempDir);
            $zip->close();
            
            // Find SQL file
            $files = array_merge(
                glob($tempDir . '/*.sql'),
                glob($tempDir . '/*/*.sql'),
                glob($tempDir . '/*/*/*.sql')
            );
            
            if (empty($files)) {
                // Clean up
                array_map('unlink', glob($tempDir . '/*'));
                rmdir($tempDir);
                throw new \Exception('No SQL file found in uploaded ZIP');
            }
            
            $sqlFile = $files[0];
            $this->restoreDatabaseFromSql($sqlFile);
            
            // Clean up
            array_map('unlink', glob($tempDir . '/*'));
            rmdir($tempDir);
        } else {
            throw new \Exception('Could not open uploaded ZIP file');
        }
    }
    
    private function ensureAdminUser(): void
    {
        // Check if admin user exists
        $adminExists = DB::table('users')
            ->where('email', 'admin@rims.live')
            ->exists();
            
        if (!$adminExists) {
            DB::table('users')->insert([
                'name' => 'Admin User',
                'email' => 'admin@rims.live',
                'password' => bcrypt('kaffeistkalt14'),
                'role' => 'super_admin',
                'is_super_admin' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}