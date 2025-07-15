<?php

namespace App\Filament\Admin\Resources\ProjectResource\Pages;

use App\Filament\Admin\Resources\ProjectResource;
use App\Models\ChecklistTemplate;
use App\Models\Module;
use App\Models\Project;
use App\Models\HotelChain;
use App\Models\HotelBrand;
use App\Models\PmsType;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateProjectWizard extends CreateRecord
{
    use HasWizard;
    
    protected static string $resource = ProjectResource::class;
    
    protected static ?string $title = 'Create Project with Wizard';
    
    protected function getSteps(): array
    {
        return [
            Step::make('Project Information')
                ->description('Basic project details')
                ->schema([
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\TextInput::make('hotel_name')
                                ->label('Hotel Name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Enter the hotel name'),
                            Forms\Components\Select::make('project_type')
                                ->label('Project Type')
                                ->options([
                                    'installation' => 'Installation',
                                    'upgrade' => 'Upgrade',
                                    'single_template' => 'Single Template',
                                ])
                                ->required()
                                ->native(false),
                            Forms\Components\TextInput::make('access_code')
                                ->label('Access Code')
                                ->maxLength(8)
                                ->default(fn () => Project::generateUniqueCode())
                                ->unique(ignoreRecord: true)
                                ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                                ->disabled()
                                ->helperText('Auto-generated access code'),
                        ])
                        ->columns(3),
                    
                    Forms\Components\Section::make('Hotel Chain & Brand')
                        ->schema([
                            Forms\Components\Select::make('hotel_chain_id')
                                ->label('Hotel Chain')
                                ->options(HotelChain::active()->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('hotel_brand_id', null)),
                            Forms\Components\Select::make('hotel_brand_id')
                                ->label('Hotel Brand')
                                ->options(function (Forms\Get $get) {
                                    $chainId = $get('hotel_chain_id');
                                    if (!$chainId) {
                                        return [];
                                    }
                                    return HotelBrand::where('hotel_chain_id', $chainId)
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->required()
                                ->disabled(fn (Forms\Get $get) => !$get('hotel_chain_id'))
                                ->helperText('Select a chain first'),
                        ])
                        ->columns(2),
                    
                    Forms\Components\Section::make('Property Management System')
                        ->schema([
                            Forms\Components\Select::make('pms_type_id')
                                ->label('PMS Type')
                                ->options(PmsType::active()->ordered()->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->helperText('Select the property management system'),
                        ]),
                    
                    Forms\Components\Section::make('Languages')
                        ->schema([
                            Forms\Components\Select::make('primary_language')
                                ->label('Primary Language')
                                ->options(fn () => \App\Models\Language::active()->ordered()->pluck('name', 'code')->toArray())
                                ->default('en') // English code
                                ->required()
                                ->searchable()
                                ->helperText('The main language used by the hotel'),
                            Forms\Components\Select::make('languages')
                                ->label('Additional Languages')
                                ->options(fn () => \App\Models\Language::active()->ordered()->pluck('name', 'code')->toArray())
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->helperText('Select all languages the hotel supports for guest communications')
                                ->dehydrateStateUsing(function ($state, Forms\Get $get) {
                                    // Ensure primary language is not in additional languages
                                    $primary = $get('primary_language');
                                    if (is_array($state) && $primary && in_array($primary, $state)) {
                                        $state = array_diff($state, [$primary]);
                                    }
                                    return array_values($state);
                                }),
                        ])
                        ->columns(1),
                    
                    Forms\Components\Section::make('Additional Information')
                        ->schema([
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->rows(3),
                            Forms\Components\TagsInput::make('notification_emails')
                                ->label('Notification Email Addresses')
                                ->placeholder('Add email address and press Enter')
                                ->helperText('Email addresses to receive the access code (optional)')
                                ->suggestions([])
                                ->splitKeys(['Tab', ','])
                                ->reorderable(),
                        ]),
                    
                    Forms\Components\Hidden::make('name')
                        ->default(''),
                ]),
                
            Step::make('Template Configuration')
                ->description('Select which modules the hotel requires')
                ->schema([
                    Forms\Components\Section::make('Letter Templates')
                        ->description('Single message templates for various communications')
                        ->schema([
                            Forms\Components\CheckboxList::make('modules.letter_templates')
                                ->label(false)
                                ->options(function () {
                                    return Module::where('category', 'single_message')
                                        ->orderBy('sort_order')
                                        ->pluck('name', 'id');
                                })
                                ->columns(2)
                                ->gridDirection('row')
                                ->bulkToggleable()
                                ->reactive()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    // Get confirmation and cancellation module IDs
                                    $confirmationModule = Module::where('code', 'confirmation')->first();
                                    $cancellationModule = Module::where('code', 'cancellation')->first();
                                    
                                    if ($confirmationModule && $cancellationModule) {
                                        $currentState = $state ?? [];
                                        
                                        // If confirmation is selected, ensure cancellation is also selected
                                        if (in_array($confirmationModule->id, $currentState)) {
                                            if (!in_array($cancellationModule->id, $currentState)) {
                                                $currentState[] = $cancellationModule->id;
                                                $set('modules.letter_templates', $currentState);
                                            }
                                        }
                                        // If cancellation is selected, ensure confirmation is also selected
                                        elseif (in_array($cancellationModule->id, $currentState)) {
                                            if (!in_array($confirmationModule->id, $currentState)) {
                                                $currentState[] = $confirmationModule->id;
                                                $set('modules.letter_templates', $currentState);
                                            }
                                        }
                                        // If one is deselected, deselect both
                                        else {
                                            $hasConfirmation = in_array($confirmationModule->id, $currentState);
                                            $hasCancellation = in_array($cancellationModule->id, $currentState);
                                            
                                            if (!$hasConfirmation && $hasCancellation) {
                                                $currentState = array_diff($currentState, [$cancellationModule->id]);
                                                $set('modules.letter_templates', array_values($currentState));
                                            } elseif ($hasConfirmation && !$hasCancellation) {
                                                $currentState = array_diff($currentState, [$confirmationModule->id]);
                                                $set('modules.letter_templates', array_values($currentState));
                                            }
                                        }
                                    }
                                }),
                        ]),
                    
                    Forms\Components\Section::make('Mailings')
                        ->description('Automated email campaigns and marketing communications')
                        ->schema([
                            Forms\Components\CheckboxList::make('modules.mailings')
                                ->label(false)
                                ->options(function () {
                                    return Module::where('category', 'mailing')
                                        ->orderBy('sort_order')
                                        ->pluck('name', 'id');
                                })
                                ->columns(2)
                                ->gridDirection('row')
                                ->bulkToggleable(),
                        ]),
                    
                    Forms\Components\Section::make('Landing Pages')
                        ->description('Interactive web pages for guest engagement')
                        ->schema([
                            Forms\Components\CheckboxList::make('modules.landing_pages')
                                ->label(false)
                                ->options(function () {
                                    return Module::where('category', 'landingpage')
                                        ->orderBy('sort_order')
                                        ->pluck('name', 'id');
                                })
                                ->columns(2)
                                ->gridDirection('row')
                                ->bulkToggleable(),
                        ]),
                    
                    Forms\Components\Section::make('Forms & Development')
                        ->description('Custom forms and development modules')
                        ->schema([
                            Forms\Components\CheckboxList::make('modules.forms')
                                ->label(false)
                                ->options(function () {
                                    return Module::whereIn('category', ['form', 'development'])
                                        ->orderBy('sort_order')
                                        ->pluck('name', 'id');
                                })
                                ->columns(2)
                                ->gridDirection('row')
                                ->bulkToggleable(),
                        ])
                        ->visible(fn () => Module::whereIn('category', ['form', 'development'])->exists()),
                ]),
        ];
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = 'setup';
        
        // Auto-generate name if empty
        if (empty($data['name']) && !empty($data['hotel_name']) && !empty($data['project_type'])) {
            $data['name'] = $data['hotel_name'] . ' - ' . ucfirst(str_replace('_', ' ', $data['project_type']));
        }
        
        // Ensure project_type has a default value
        if (empty($data['project_type'])) {
            $data['project_type'] = 'installation';
        }
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // Collect all selected module IDs
        $selectedModuleIds = [];
        
        if (isset($this->data['modules'])) {
            foreach ($this->data['modules'] as $category => $moduleIds) {
                if (is_array($moduleIds)) {
                    $selectedModuleIds = array_merge($selectedModuleIds, $moduleIds);
                }
            }
        }
        
        // Remove duplicates
        $selectedModuleIds = array_unique($selectedModuleIds);
        
        // Get modules with their dependencies
        $modules = Module::whereIn('id', $selectedModuleIds)->get();
        $allModuleSlugs = [];
        
        foreach ($modules as $module) {
            $allModuleSlugs[] = $module->slug;
            
            // Add dependencies - but avoid infinite loops for circular dependencies
            if ($module->dependencies) {
                foreach ($module->dependencies as $dep) {
                    // Don't add if it's already in the list to avoid circular dependencies
                    if (!in_array($dep, $allModuleSlugs)) {
                        $allModuleSlugs[] = $dep;
                    }
                }
            }
        }
        
        // Get unique slugs and load all required modules
        $allModuleSlugs = array_unique($allModuleSlugs);
        $allModules = Module::whereIn('slug', $allModuleSlugs)->get();
        
        // Attach modules to project
        foreach ($allModules as $module) {
            $this->record->modules()->attach($module->id, [
                'status' => 'pending',
                'progress' => 0,
            ]);
        }
    }
}