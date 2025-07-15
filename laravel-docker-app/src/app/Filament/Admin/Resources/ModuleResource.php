<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ModuleResource\Pages;
use App\Filament\Admin\Resources\ModuleResource\RelationManagers;
use App\Models\Module;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    
    protected static ?string $navigationGroup = 'System Configuration';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Module Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('slug', \Illuminate\Support\Str::slug($state));
                                // Convert to proper code format: "Transfer Confirmation" -> "Transfer_Confirmation"
                                $code = str_replace(' ', '_', ucwords(strtolower($state)));
                                $set('code', $code);
                            }),
                        Forms\Components\Hidden::make('slug')
                            ->dehydrated(),
                        Forms\Components\TextInput::make('code')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated()
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-generated from name'),
                        Forms\Components\Select::make('category')
                            ->required()
                            ->options(Module::CATEGORIES)
                            ->default('single_message'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->maxLength(1000),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active')
                            ->helperText('Inactive modules will not be available for new projects'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Module Setup Fields')
                    ->description('Define fields that must be completed when this module is selected for a project')
                    ->schema([
                        Forms\Components\Repeater::make('setup_fields')
                            ->label(false)
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('field_name')
                                            ->label('Field Name')
                                            ->required()
                                            ->placeholder('e.g., template_id')
                                            ->helperText('Internal identifier'),
                                        Forms\Components\TextInput::make('label')
                                            ->label('Display Label')
                                            ->required()
                                            ->placeholder('e.g., Template ID')
                                            ->helperText('Label shown to users'),
                                    ]),
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
                                                'multiselect' => 'Multi-Select',
                                                'checkbox' => 'Checkbox',
                                                'boolean' => 'Yes/No Toggle',
                                                'date' => 'Date Picker',
                                                'datetime' => 'Date & Time',
                                                'file' => 'File Upload',
                                                'color' => 'Color Picker',
                                            ])
                                            ->required()
                                            ->reactive(),
                                        Forms\Components\Toggle::make('required')
                                            ->label('Required Field')
                                            ->default(true),
                                    ]),
                                Forms\Components\Textarea::make('description')
                                    ->label('Help Text')
                                    ->rows(2)
                                    ->placeholder('Provide instructions or additional context')
                                    ->columnSpanFull(),
                                Forms\Components\TagsInput::make('options')
                                    ->label('Options (for dropdown/multi-select fields)')
                                    ->placeholder('Add option and press Enter')
                                    ->visible(fn (Forms\Get $get) => in_array($get('type'), ['select', 'multiselect']))
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('e.g., max:255, url, numeric')
                                    ->helperText('Laravel validation rules (optional)')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('default_value')
                                    ->label('Default Value')
                                    ->placeholder('Optional default value')
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel('Add Setup Field')
                            ->reorderable()
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['label']) ? $state['label'] : 'New Field'
                            )
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Dependencies & Configuration')
                    ->schema([
                        Forms\Components\Select::make('dependencies')
                            ->multiple()
                            ->searchable()
                            ->options(function ($record) {
                                return Module::where('id', '!=', $record?->id ?? 0)
                                    ->pluck('name', 'slug');
                            })
                            ->helperText('Select modules that must be completed before this one'),
                        Forms\Components\Toggle::make('requires_approval')
                            ->label('Requires Approval')
                            ->helperText('Module completion must be approved by admin'),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->label('Display Order')
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Room Type Requirements')
                    ->description('Configure which room details are required when this module is selected')
                    ->schema([
                        Forms\Components\Toggle::make('requires_room_details')
                            ->label('Requires Room Details')
                            ->helperText('Enable to require room images and descriptions for this module')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) {
                                    $set('requires_room_short_description', false);
                                    $set('requires_room_long_description', false);
                                    $set('requires_room_main_image', false);
                                    $set('requires_room_slideshow_images', false);
                                }
                            }),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('requires_room_short_description')
                                    ->label('Short Description')
                                    ->helperText('Brief room description for emails')
                                    ->disabled(fn (Forms\Get $get) => !$get('requires_room_details')),
                                    
                                Forms\Components\Toggle::make('requires_room_long_description')
                                    ->label('Long Description')
                                    ->helperText('Detailed room description for offers')
                                    ->disabled(fn (Forms\Get $get) => !$get('requires_room_details')),
                                    
                                Forms\Components\Toggle::make('requires_room_main_image')
                                    ->label('Main Image')
                                    ->helperText('Primary room image')
                                    ->disabled(fn (Forms\Get $get) => !$get('requires_room_details')),
                                    
                                Forms\Components\Toggle::make('requires_room_slideshow_images')
                                    ->label('Slideshow Images')
                                    ->helperText('Additional images for landing pages')
                                    ->disabled(fn (Forms\Get $get) => !$get('requires_room_details')),
                            ]),
                            
                        Forms\Components\Toggle::make('allow_room_details_toggle')
                            ->label('Allow Optional Room Details')
                            ->helperText('Show toggle in reservation settings to optionally include room details in email templates')
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Datastore Tables')
                    ->description('Configure which datastore tables are required for this module')
                    ->schema([
                        Forms\Components\CheckboxList::make('datastore_tables')
                            ->label('Required Datastore Tables')
                            ->options(static::getDatastoreTableOptions())
                            ->columns(3)
                            ->helperText('Select tables that should be enabled when this module is active. These will be added to the standard tables defined in general settings.')
                            ->searchable()
                            ->bulkToggleable()
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\BadgeColumn::make('category')
                    ->colors([
                        'primary' => 'single_message',
                        'success' => 'mailing',
                        'info' => 'landingpage',
                        'warning' => 'form',
                        'danger' => 'development',
                    ])
                    ->formatStateUsing(fn (string $state): string => Module::CATEGORIES[$state] ?? $state),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('setup_fields')
                    ->label('Setup Fields')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) : 0)
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('projects_count')
                    ->label('Projects')
                    ->counts('projects')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('requires_approval')
                    ->label('Approval')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('dependencies')
                    ->label('Dependencies')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) : 0)
                    ->badge()
                    ->color('warning')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(Module::CATEGORIES),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->placeholder('All'),
                Tables\Filters\TernaryFilter::make('requires_approval')
                    ->label('Requires Approval')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession();
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
            'index' => Pages\ListModules::route('/'),
            'create' => Pages\CreateModule::route('/create'),
            'edit' => Pages\EditModule::route('/{record}/edit'),
        ];
    }
    
    protected static function getDatastoreTableOptions(): array
    {
        return [
            'attachments' => 'Attachments',
            'buildings' => 'Buildings',
            'calendar' => 'Calendar',
            'cancellationPolicies' => 'Cancellation Policies',
            'cancellationReasons' => 'Cancellation Reasons',
            'colors' => 'Colors',
            'depositPolicies' => 'Deposit Policies',
            'extraCategories' => 'Extra Categories',
            'extras' => 'Extras',
            'extrasItems' => 'Extras Items',
            'fixedCharges' => 'Fixed Charges',
            'greetings' => 'Greetings',
            'guaranteeTypes' => 'Guarantee Types',
            'infos' => 'Infos',
            'landingpages' => 'Landing Pages',
            'links' => 'Links',
            'mealplans' => 'Meal Plans',
            'membershipPrograms' => 'Membership Programs',
            'memberships' => 'Memberships',
            'operaTransactionCodes' => 'Opera Transaction Codes',
            'packages' => 'Packages',
            'partnerLinks' => 'Partner Links',
            'paymentInterfaceOptions' => 'Payment Interface Options',
            'paymentMethods' => 'Payment Methods',
            'periodes' => 'Periods',
            'preferenceValues' => 'Preference Values',
            'preferences' => 'Preferences',
            'promotions' => 'Promotions',
            'properties' => 'Properties',
            'rates' => 'Rates',
            'roomCategories' => 'Room Categories',
            'roomFeatures' => 'Room Features',
            'roomTypes' => 'Room Types',
            'roomUpsell' => 'Room Upsell',
            'rooms' => 'Rooms',
            'seasons' => 'Seasons',
            'socialMedia' => 'Social Media',
            'specialRequests' => 'Special Requests',
            'tagMapping' => 'Tag Mapping',
            'tags' => 'Tags',
            'taxes' => 'Taxes',
            'templates' => 'Templates',
            'transferStations' => 'Transfer Stations',
            'transfers' => 'Transfers',
            'users' => 'Users',
            'wordings' => 'Wordings',
        ];
    }
}