<?php

namespace App\Filament\Admin\Resources\SystemResource\Pages;

use App\Filament\Admin\Resources\SystemResource;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\On;

class LogViewer extends Page
{
    protected static string $resource = SystemResource::class;

    protected static string $view = 'filament.admin.resources.system-resource.pages.log-viewer';
    
    protected static ?string $title = 'Log Viewer';
    
    protected static ?string $navigationLabel = 'Logs';
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected function getLayoutData(): array
    {
        return [
            ...parent::getLayoutData(),
            'maxContentWidth' => 'full',
        ];
    }
    
    public $logs = '';
    public $autoRefresh = true;
    public $refreshInterval = 5000; // 5 seconds
    public $lineCount = 2000;
    public $selectedLogFile = 'laravel.log';
    public $availableLogFiles = [];
    
    public function mount(): void
    {
        $this->loadAvailableLogFiles();
        $this->loadLogs();
    }
    
    public function loadAvailableLogFiles(): void
    {
        $logPath = storage_path('logs');
        $this->availableLogFiles = [];
        
        if (is_dir($logPath)) {
            $files = scandir($logPath);
            foreach ($files as $file) {
                if (preg_match('/\.log$/', $file)) {
                    $this->availableLogFiles[] = $file;
                }
            }
            sort($this->availableLogFiles);
        }
        
        // If selected file doesn't exist, use the first available
        if (!in_array($this->selectedLogFile, $this->availableLogFiles) && count($this->availableLogFiles) > 0) {
            $this->selectedLogFile = $this->availableLogFiles[0];
        }
    }
    
    public function loadLogs(): void
    {
        $logFile = storage_path('logs/' . $this->selectedLogFile);
        
        if (file_exists($logFile)) {
            // Get specified number of lines
            $lines = $this->tailFile($logFile, $this->lineCount);
            $this->logs = $this->formatLogs($lines);
        } else {
            $this->logs = 'Log file not found.';
        }
    }
    
    private function formatLogs(array $lines): string
    {
        $formatted = [];
        
        foreach ($lines as $line) {
            // Add color coding based on log level
            if (strpos($line, '.ERROR:') !== false || strpos($line, 'ERROR') !== false) {
                $formatted[] = '<span style="color: #ef4444; font-weight: bold;">' . htmlspecialchars($line) . '</span>';
            } elseif (strpos($line, '.WARNING:') !== false || strpos($line, 'WARNING') !== false) {
                $formatted[] = '<span style="color: #f59e0b;">' . htmlspecialchars($line) . '</span>';
            } elseif (strpos($line, '.INFO:') !== false || strpos($line, 'INFO') !== false) {
                $formatted[] = '<span style="color: #3b82f6;">' . htmlspecialchars($line) . '</span>';
            } elseif (strpos($line, '.DEBUG:') !== false || strpos($line, 'DEBUG') !== false) {
                $formatted[] = '<span style="color: #10b981;">' . htmlspecialchars($line) . '</span>';
            } elseif (preg_match('/^\[[\d-]+ [\d:]+\]/', $line)) {
                // Timestamp lines
                $formatted[] = '<span style="color: #6b7280;">' . htmlspecialchars($line) . '</span>';
            } elseif (strpos($line, 'Stack trace:') !== false || strpos($line, '#') === 0) {
                // Stack trace
                $formatted[] = '<span style="color: #9ca3af; font-size: 0.7rem;">' . htmlspecialchars($line) . '</span>';
            } else {
                $formatted[] = htmlspecialchars($line);
            }
        }
        
        return implode("\n", $formatted);
    }
    
    private function tailFile($filepath, $lines = 100): array
    {
        $data = [];
        $fp = fopen($filepath, "r");
        
        if (!$fp) {
            return $data;
        }
        
        fseek($fp, -1, SEEK_END);
        $position = ftell($fp);
        $currentLine = '';
        $lineCount = 0;
        
        while ($position > 0 && $lineCount < $lines) {
            $char = fgetc($fp);
            
            if ($char === "\n" && $currentLine !== '') {
                array_unshift($data, $currentLine);
                $currentLine = '';
                $lineCount++;
            } else {
                $currentLine = $char . $currentLine;
            }
            
            fseek($fp, $position--);
        }
        
        if ($currentLine !== '') {
            array_unshift($data, $currentLine);
        }
        
        fclose($fp);
        
        return $data;
    }
    
    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        // Dispatch event to JavaScript
        $this->dispatch('auto-refresh-toggled', ['enabled' => $this->autoRefresh]);
    }
    
    public function clearLogs(): void
    {
        $logFile = storage_path('logs/' . $this->selectedLogFile);
        
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
            $this->logs = 'Logs cleared.';
        }
    }
    
    #[On('refresh-logs')]
    public function refreshLogs(): void
    {
        $this->loadLogs();
    }
    
    public function changeLogFile(): void
    {
        $this->loadLogs();
    }
}
