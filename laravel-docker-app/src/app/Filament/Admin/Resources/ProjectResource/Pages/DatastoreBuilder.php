<?php

namespace App\Filament\Admin\Resources\ProjectResource\Pages;

use App\Filament\Admin\Resources\ProjectResource;
use App\Models\DatastoreConfiguration;
use App\Models\Setting;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;

class DatastoreBuilder extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.admin.resources.project-resource.pages.datastore-builder-new';
    
    protected static ?string $title = '';
    
    public $configuration;
    public $masterTemplate;
    public $defaultStructure;
    public $projectModules;
    public $chainCode;
    public $allowedTables;
    
    protected function getLayoutData(): array
    {
        return [
            ...parent::getLayoutData(),
            'maxContentWidth' => 'full',
        ];
    }

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        // Load existing configuration or create new one
        $this->configuration = DatastoreConfiguration::firstOrCreate(
            ['project_id' => $this->record->id, 'name' => 'default'],
            [
                'description' => 'Default datastore configuration',
                'configuration' => [],
                'disabled_tables' => [],
                'custom_fields' => [],
                'module_overrides' => [],
                'chain_overrides' => [],
                'is_active' => true,
            ]
        );
        
        // Load master template
        $this->masterTemplate = DatastoreConfiguration::loadMasterTemplate();
        
        // Load default structure from settings
        $defaultStructureJson = Setting::get('default_datastore_structure', '');
        if (!empty($defaultStructureJson)) {
            $this->defaultStructure = json_decode($defaultStructureJson, true);
        } else {
            // Fallback to master template if no default structure is set
            $this->defaultStructure = $this->masterTemplate;
        }
        
        // Debug log
        \Log::info('Default structure loaded:', ['has_structure' => !empty($this->defaultStructure)]);
        
        // Get project modules
        $this->projectModules = $this->record->modules->pluck('name', 'code')->toArray();
        
        // Get chain code
        $this->chainCode = $this->record->hotelChain?->code;
        
        // Get allowed tables
        $this->allowedTables = $this->configuration->getAllowedTables();
    }
    
    public function getTitle(): string | Htmlable
    {
        return $this->record->hotel_name;
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reset')
                ->label('Reset to Default')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reset Configuration?')
                ->modalDescription('This will reset your configuration to the default template. All customizations will be lost.')
                ->action(function () {
                    $this->configuration->update([
                        'configuration' => [],
                        'disabled_tables' => [],
                        'custom_fields' => [],
                        'module_overrides' => [],
                        'chain_overrides' => [],
                    ]);
                    
                    Notification::make()
                        ->title('Configuration reset')
                        ->success()
                        ->send();
                    
                    return redirect()->to(static::getUrl(['record' => $this->record]));
                }),
                
            Actions\Action::make('import')
                ->label('Import JSON')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('json_file')
                        ->label('JSON Configuration File')
                        ->acceptedFileTypes(['application/json'])
                        ->required()
                ])
                ->action(function (array $data) {
                    // Import logic will be handled by Vue component
                    Notification::make()
                        ->title('Please use the import button in the builder interface')
                        ->info()
                        ->send();
                }),
                
            Actions\Action::make('export')
                ->label('Export JSON')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    // Export only the overrides/changes, not the full configuration
                    $overrides = $this->configuration->configuration ?? ['tables' => []];
                    $filename = "datastore_overrides_{$this->record->id}_" . date('Y-m-d_His') . '.json';
                    
                    return response()->streamDownload(function () use ($overrides) {
                        echo json_encode($overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    }, $filename);
                }),
                
            Actions\Action::make('save')
                ->label('Save Configuration')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('saveConfiguration'),
                
            Actions\Action::make('saveAndBack')
                ->label('Save & Back')
                ->icon('heroicon-o-arrow-up-left')
                ->color('success')
                ->action(function () {
                    // Save configuration first
                    $this->saveConfiguration();
                    
                    // Show success notification
                    Notification::make()
                        ->title('Configuration saved')
                        ->success()
                        ->send();
                    
                    // Redirect to projects index (overview)
                    return redirect()->to(ProjectResource::getUrl('index'));
                }),
        ];
    }
    
    public function saveConfiguration(): void
    {
        // Dispatch event to Vue component to trigger save
        $this->dispatch('save-configuration');
    }
    
    // Method called from JavaScript to actually save the configuration
    public function saveDatastoreConfiguration($configuration): void
    {
        // Update the configuration in the database
        $this->configuration->update([
            'configuration' => $configuration
        ]);
        
        // Show success notification
        Notification::make()
            ->title('Configuration saved successfully')
            ->success()
            ->send();
    }
}