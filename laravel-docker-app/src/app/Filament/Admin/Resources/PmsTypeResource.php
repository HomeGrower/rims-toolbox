<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PmsTypeResource\Pages;
use App\Filament\Admin\Resources\PmsTypeResource\RelationManagers;
use App\Models\PmsType;
use App\Models\Module;
use App\Models\HotelBrand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str;

class PmsTypeResource extends Resource
{
    protected static ?string $model = PmsType::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    
    protected static ?string $navigationGroup = 'System Configuration';
    
    protected static ?string $navigationLabel = 'PMS Types';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                $set('code', PmsType::generateUniqueCode($state))
                            ),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Auto-generated from name'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('IT Settings')
                    ->description('Define the IT configuration fields that hotels must complete during the setup process')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible()
                    ->collapsed(false)
                    ->extraAttributes([
                        'class' => 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-700'
                    ])
                    ->schema([
                        Forms\Components\Toggle::make('reservation_settings_config.is_active')
                            ->label('Enable Reservation Settings')
                            ->helperText('Toggle to enable/disable all reservation settings for this PMS type')
                            ->default(true)
                            ->reactive()
                            ->columnSpanFull(),
                        
                        Forms\Components\Placeholder::make('conditional_info')
                            ->label('💡 Tip: Conditional Fields')
                            ->content('You can create conditional fields by setting a field type to "Dropdown", "Checkbox", or "Yes/No Toggle". Then, you can define which additional fields should appear based on the selected value.')
                            ->columnSpanFull(),
                        
                        Forms\Components\Repeater::make('setup_requirements')
                            ->label(false)
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['label']) ? $state['label'] : 'New Field'
                            )
                            ->collapsible()
                            ->collapsed()
                            ->cloneable()
                            ->reorderable()
                            ->schema([
                                Forms\Components\Hidden::make('field_name'),
                                Forms\Components\TextInput::make('label')
                                    ->label('Display Label')
                                    ->required()
                                    ->placeholder('e.g., Server IP Address')
                                    ->helperText('Label shown to users')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            // Generate field_name from label
                                            $fieldName = Str::slug(str_replace(' ', '_', strtolower($state)), '_');
                                            $set('field_name', $fieldName);
                                        }
                                    })
                                    ->columnSpanFull(),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('type')
                                            ->label('Field Type')
                                            ->options([
                                                'text' => 'Text Input',
                                                'password' => 'Password',
                                                'email' => 'Email',
                                                'url' => 'URL',
                                                'number' => 'Number',
                                                'textarea' => 'Text Area',
                                                'select' => 'Dropdown',
                                                'checkbox' => 'Checkbox',
                                                'boolean' => 'Yes/No Toggle',
                                                'date' => 'Date Picker',
                                                'info' => 'Info Text (Display Only)',
                                            ])
                                            ->required()
                                            ->reactive(),
                                        Forms\Components\Toggle::make('required')
                                            ->label('Required Field')
                                            ->default(true)
                                            ->visible(fn (Forms\Get $get) => $get('type') !== 'info'),
                                    ]),
                                Forms\Components\Textarea::make('description')
                                    ->label('Help Text')
                                    ->rows(2)
                                    ->placeholder('Provide helpful instructions for the user')
                                    ->extraInputAttributes(['style' => 'white-space: pre-wrap;'])
                                    ->columnSpanFull(),
                                Forms\Components\TagsInput::make('options')
                                    ->label('Options (for dropdown fields)')
                                    ->placeholder('Add option and press Enter')
                                    ->visible(fn (Forms\Get $get) => $get('type') === 'select')
                                    ->reactive()
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('e.g., ip, min:8, max:255')
                                    ->helperText('Laravel validation rules (optional)')
                                    ->columnSpanFull(),
                                
                                // Conditional Fields Section
                                Forms\Components\Section::make('Conditional Fields')
                                    ->description('Define fields that appear based on the value of this field')
                                    ->icon('heroicon-o-adjustments-horizontal')
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->extraAttributes([
                                        'class' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700'
                                    ])
                                    ->schema([
                                        Forms\Components\Repeater::make('conditions')
                                            ->label(false)
                                            ->itemLabel(fn (array $state): ?string => 
                                                isset($state['value']) ? 'When: ' . $state['value'] : 'New Condition'
                                            )
                                            ->collapsible()
                                            ->collapsed()
                                            ->schema([
                                                Forms\Components\Select::make('value')
                                                    ->label('When value equals')
                                                    ->options(function (Forms\Get $get) {
                                                        // Get parent field type
                                                        $parentType = $get('../../type');
                                                        
                                                        // For select fields, get the options
                                                        if ($parentType === 'select') {
                                                            $options = $get('../../options');
                                                            if (!empty($options) && is_array($options)) {
                                                                $selectOptions = [];
                                                                foreach ($options as $option) {
                                                                    $selectOptions[$option] = $option;
                                                                }
                                                                return $selectOptions;
                                                            }
                                                        }
                                                        
                                                        // For checkbox/boolean fields, return true/false options
                                                        if ($parentType === 'checkbox' || $parentType === 'boolean') {
                                                            return [
                                                                'true' => 'Yes/Checked',
                                                                'false' => 'No/Unchecked'
                                                            ];
                                                        }
                                                        
                                                        return [];
                                                    })
                                                    ->placeholder('Select a value')
                                                    ->helperText('Select one of the options defined above')
                                                    ->required()
                                                    ->reactive()
                                                    ->columnSpanFull(),
                                                Forms\Components\Repeater::make('fields')
                                                    ->label('Show these fields')
                                                    ->itemLabel(fn (array $state): ?string => 
                                                        isset($state['label']) ? $state['label'] : 'New Field'
                                                    )
                                                    ->collapsible()
                                                    ->collapsed()
                                                    ->schema([
                                                        Forms\Components\Hidden::make('field_name'),
                                                        Forms\Components\TextInput::make('label')
                                                            ->label('Field Label')
                                                            ->required()
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                                if ($state) {
                                                                    $fieldName = Str::slug(str_replace(' ', '_', strtolower($state)), '_');
                                                                    $set('field_name', $fieldName);
                                                                }
                                                            }),
                                                        Forms\Components\Select::make('type')
                                                            ->label('Field Type')
                                                            ->options([
                                                                'text' => 'Text Input',
                                                                'password' => 'Password',
                                                                'email' => 'Email',
                                                                'url' => 'URL',
                                                                'number' => 'Number',
                                                                'textarea' => 'Text Area',
                                                                'select' => 'Dropdown',
                                                                'checkbox' => 'Checkbox',
                                                                'boolean' => 'Yes/No Toggle',
                                                                'info' => 'Info Text (Display Only)',
                                                            ])
                                                            ->required()
                                                            ->reactive(),
                                                        Forms\Components\Toggle::make('required')
                                                            ->label('Required')
                                                            ->default(false)
                                                            ->visible(fn (Forms\Get $get) => $get('type') !== 'info'),
                                                        Forms\Components\Textarea::make('description')
                                                            ->label('Help Text')
                                                            ->rows(2)
                                                            ->extraInputAttributes(['style' => 'white-space: pre-wrap;']),
                                                        Forms\Components\TagsInput::make('options')
                                                            ->label('Options')
                                                            ->placeholder('Add option')
                                                            ->visible(fn (Forms\Get $get) => $get('type') === 'select'),
                                                    ])
                                                    ->columns(2),
                                            ])
                                            ,
                                    ])
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->visible(fn (Forms\Get $get) => in_array($get('type'), ['select', 'checkbox', 'boolean']) && $get('type') !== 'info'),
                            ])
                            ->addActionLabel('Add IT Configuration Field')
                            ->default([
                                [
                                    'field_name' => 'pms_system',
                                    'label' => 'PMS System',
                                    'type' => 'select',
                                    'required' => true,
                                    'description' => 'Select your Property Management System',
                                    'options' => ['opera', 'mews', 'protel', 'other'],
                                    'conditions' => [
                                        [
                                            'value' => 'opera',
                                            'fields' => [
                                                [
                                                    'field_name' => 'opera_version',
                                                    'label' => 'Opera Version',
                                                    'type' => 'text',
                                                    'required' => true,
                                                    'description' => 'Enter your Opera Cloud version'
                                                ],
                                                [
                                                    'field_name' => 'opera_interface',
                                                    'label' => 'Interface Type',
                                                    'type' => 'select',
                                                    'options' => ['REST', 'SOAP', 'XML'],
                                                    'required' => true
                                                ]
                                            ]
                                        ],
                                        [
                                            'value' => 'mews',
                                            'fields' => [
                                                [
                                                    'field_name' => 'mews_api_key',
                                                    'label' => 'API Key',
                                                    'type' => 'password',
                                                    'required' => true,
                                                    'description' => 'Your Mews API key'
                                                ],
                                                [
                                                    'field_name' => 'mews_environment',
                                                    'label' => 'Environment',
                                                    'type' => 'select',
                                                    'options' => ['production', 'demo'],
                                                    'required' => true
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ])
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Module-Specific Configurations')
                    ->description('Add additional fields that appear only when specific modules are selected')
                    ->icon('heroicon-o-puzzle-piece')
                    ->collapsible()
                    ->collapsed(true)
                    ->extraAttributes([
                        'class' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700'
                    ])
                    ->schema([
                        Forms\Components\Repeater::make('module_configurations')
                            ->label(false)
                            ->schema([
                                Forms\Components\Select::make('module')
                                    ->label('Module')
                                    ->options(Module::pluck('name', 'slug'))
                                    ->required()
                                    ->searchable()
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('requirements')
                                    ->label('Additional Fields for this Module')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('field_name')
                                                    ->label('Field Name')
                                                    ->required()
                                                    ->placeholder('e.g., interface_version'),
                                                Forms\Components\TextInput::make('label')
                                                    ->label('Display Label')
                                                    ->required()
                                                    ->placeholder('e.g., Interface Version'),
                                            ]),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('type')
                                                    ->label('Field Type')
                                                    ->options([
                                                        'text' => 'Text Input',
                                                        'password' => 'Password',
                                                        'select' => 'Dropdown',
                                                        'checkbox' => 'Checkbox',
                                                        'boolean' => 'Yes/No Toggle',
                                                    ])
                                                    ->required(),
                                                Forms\Components\Toggle::make('required')
                                                    ->label('Required')
                                                    ->default(true),
                                            ]),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Help Text')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ])
                                    ->addActionLabel('Add Field')
                                    ->collapsed()
                                    ->collapsible(),
                            ])
                            ->addActionLabel('Add Module Configuration')
                            ->columnSpanFull()
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(function (array $state): ?string {
                                if (isset($state['module'])) {
                                    $module = Module::where('slug', $state['module'])->first();
                                    return $module ? $module->name : 'Module Configuration';
                                }
                                return 'New Module Configuration';
                            }),
                    ]),
                
                Forms\Components\Section::make('Brand-Specific Configurations')
                    ->description('Add additional fields that appear only for specific hotel brands')
                    ->icon('heroicon-o-building-office')
                    ->collapsible()
                    ->collapsed(true)
                    ->extraAttributes([
                        'class' => 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-700'
                    ])
                    ->schema([
                        Forms\Components\Repeater::make('brand_configurations')
                            ->label(false)
                            ->schema([
                                Forms\Components\Select::make('brand')
                                    ->label('Hotel Brand')
                                    ->options(function () {
                                        return HotelBrand::with('hotelChain')
                                            ->get()
                                            ->mapWithKeys(function ($brand) {
                                                return [$brand->id => $brand->hotelChain->name . ' - ' . $brand->name];
                                            });
                                    })
                                    ->required()
                                    ->searchable()
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('requirements')
                                    ->label('Additional Fields for this Brand')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('field_name')
                                                    ->label('Field Name')
                                                    ->required()
                                                    ->placeholder('e.g., brand_code'),
                                                Forms\Components\TextInput::make('label')
                                                    ->label('Display Label')
                                                    ->required()
                                                    ->placeholder('e.g., Brand Code'),
                                            ]),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('type')
                                                    ->label('Field Type')
                                                    ->options([
                                                        'text' => 'Text Input',
                                                        'password' => 'Password',
                                                        'select' => 'Dropdown',
                                                        'checkbox' => 'Checkbox',
                                                        'boolean' => 'Yes/No Toggle',
                                                    ])
                                                    ->required(),
                                                Forms\Components\Toggle::make('required')
                                                    ->label('Required')
                                                    ->default(true),
                                            ]),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Help Text')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ])
                                    ->addActionLabel('Add Field')
                                    ->collapsed()
                                    ->collapsible(),
                            ])
                            ->addActionLabel('Add Brand Configuration')
                            ->columnSpanFull()
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(function (array $state): ?string {
                                if (isset($state['brand'])) {
                                    $brand = HotelBrand::find($state['brand']);
                                    return $brand ? $brand->name . ' Configuration' : 'Brand Configuration';
                                }
                                return 'New Brand Configuration';
                            }),
                    ]),
                
                Forms\Components\Section::make('Reservation Settings Configuration')
                    ->description('Configure which policy fields should be available in reservation settings for this PMS type')
                    ->icon('heroicon-o-calendar-days')
                    ->collapsible()
                    ->collapsed(true)
                    ->extraAttributes([
                        'class' => 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-700'
                    ])
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                // Cancellation Policies
                                Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('reservation_settings_config.cancellation_policies')
                                    ->label('Enable Cancellation Policies')
                                    ->helperText('Allow hotels to configure cancellation policies mapping')
                                    ->default(false)
                                    ->reactive(),
                                Forms\Components\FileUpload::make('policy_example_images.cancellation_policies')
                                    ->label('Cancellation Policies Example')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('pms-examples')
                                    ->visibility('public')
                                    ->helperText('Example showing how cancellation policies should be displayed')
                                    ->visible(fn (Forms\Get $get) => $get('reservation_settings_config.is_active') === true && $get('reservation_settings_config.cancellation_policies') === true),
                            ]),
                        
                        // Special Requests
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('reservation_settings_config.special_requests')
                                    ->label('Enable Special Requests')
                                    ->helperText('Allow hotels to configure special requests mapping')
                                    ->default(false)
                                    ->reactive(),
                                Forms\Components\FileUpload::make('policy_example_images.special_requests')
                                    ->label('Special Requests Example')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('pms-examples')
                                    ->visibility('public')
                                    ->helperText('Example showing how special requests should be displayed')
                                    ->visible(fn (Forms\Get $get) => $get('reservation_settings_config.is_active') === true && $get('reservation_settings_config.special_requests') === true),
                            ]),
                        
                        // Deposit Policies
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('reservation_settings_config.deposit_policies')
                                    ->label('Enable Deposit Policies')
                                    ->helperText('Allow hotels to configure deposit policies mapping')
                                    ->default(false)
                                    ->reactive(),
                                Forms\Components\FileUpload::make('policy_example_images.deposit_policies')
                                    ->label('Deposit Policies Example')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('pms-examples')
                                    ->visibility('public')
                                    ->helperText('Example showing how deposit policies should be displayed')
                                    ->visible(fn (Forms\Get $get) => $get('reservation_settings_config.is_active') === true && $get('reservation_settings_config.deposit_policies') === true),
                            ]),
                        
                        // Payment Methods
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('reservation_settings_config.payment_methods')
                                    ->label('Enable Payment Methods')
                                    ->helperText('Allow hotels to configure payment methods mapping')
                                    ->default(false)
                                    ->reactive(),
                                Forms\Components\FileUpload::make('policy_example_images.payment_methods')
                                    ->label('Payment Methods Example')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('pms-examples')
                                    ->visibility('public')
                                    ->helperText('Example showing how payment methods should be displayed')
                                    ->visible(fn (Forms\Get $get) => $get('reservation_settings_config.is_active') === true && $get('reservation_settings_config.payment_methods') === true),
                            ]),
                        
                        // Transfer Types
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('reservation_settings_config.transfer_types')
                                    ->label('Enable Transfer Types')
                                    ->helperText('Allow hotels to configure transfer types mapping')
                                    ->default(false)
                                    ->reactive(),
                                Forms\Components\FileUpload::make('policy_example_images.transfer_types')
                                    ->label('Transfer Types Example')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('pms-examples')
                                    ->visibility('public')
                                    ->helperText('Example showing how transfer types should be displayed')
                                    ->visible(fn (Forms\Get $get) => $get('reservation_settings_config.is_active') === true && $get('reservation_settings_config.transfer_types') === true),
                            ]),
                            
                        // Room Details Example
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\FileUpload::make('policy_example_images.room_details')
                                    ->label('Room Details Example Image')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('pms-examples')
                                    ->visibility('public')
                                    ->helperText('Example showing how room details appear in email templates')
                                    ->columnSpanFull(),
                            ]),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('reservation_settings_config.is_active') === true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('projects_count')
                    ->counts('projects')
                    ->label('Projects'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->placeholder('All'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPmsTypes::route('/'),
            'create' => Pages\CreatePmsType::route('/create'),
            'edit' => Pages\EditPmsType::route('/{record}/edit'),
        ];
    }
}
