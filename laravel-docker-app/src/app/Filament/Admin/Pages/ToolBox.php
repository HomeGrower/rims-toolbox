<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\DatastoreConfiguration;
use App\Models\Setting;

class ToolBox extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    
    protected static ?string $navigationLabel = 'Datastore Builder';
    
    protected static ?string $title = '';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationGroup = 'Tool-Box';

    protected static string $view = 'filament.admin.pages.tool-box';
    
    protected function getLayoutData(): array
    {
        return [
            ...parent::getLayoutData(),
            'maxContentWidth' => 'full',
        ];
    }

    public function mount(): void
    {
        // Load datastore structure data
        $this->loadDatastoreStructure();
    }

    protected function loadDatastoreStructure(): void
    {
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
    }

    public $defaultStructure = [];
    public $masterTemplate = [];
}