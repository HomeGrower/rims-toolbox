<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\HotelBrandResource\Pages;
use App\Filament\Admin\Resources\HotelBrandResource\RelationManagers;
use App\Helpers\FontAwesomeIcons;
use App\Models\HotelBrand;
use App\Models\Module;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class HotelBrandResource extends Resource
{
    protected static ?string $model = HotelBrand::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    
    // Hide from navigation - only accessible via HotelChain relation
    protected static bool $shouldRegisterNavigation = false;
    
    protected static ?string $modelLabel = 'Hotel Brand';
    
    protected static ?string $pluralModelLabel = 'Hotel Brands';
    
    public static function getBreadcrumb(): string
    {
        return 'Brand';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Brand Information')
                    ->schema([
                        Forms\Components\View::make('vendor.filament.components.brand-logo-styles'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Group::make([
                                    Forms\Components\Select::make('hotel_chain_id')
                                        ->label('Hotel Chain')
                                        ->relationship('hotelChain', 'name')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->disabled(fn ($context) => $context === 'edit'),
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Forms\Set $set, $context) {
                                            if ($context === 'create') {
                                                $set('code', strtoupper(Str::slug($state, '_')));
                                            }
                                        }),
                                    Forms\Components\TextInput::make('code')
                                        ->required()
                                        ->maxLength(10)
                                        ->unique(ignoreRecord: true)
                                        ->helperText('Unique brand code'),
                                    Forms\Components\Toggle::make('is_active')
                                        ->required()
                                        ->default(true),
                                ])->columnSpan(1),
                                Forms\Components\Group::make([
                                    Forms\Components\FileUpload::make('logo')
                                        ->label('Brand Logo')
                                        ->image()
                                        ->directory('brand-logos')
                                        ->visibility('public')
                                        ->imagePreviewHeight('200')
                                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'])
                                        ->helperText('Recommended: 16:9 aspect ratio (e.g., 1500x844px)'),
                                ])
                                ->extraAttributes([
                                    'style' => 'background-color: white; padding: 1rem; border-radius: 0.5rem;',
                                ])
                                ->columnSpan(1),
                            ]),
                    ]),
                
                Forms\Components\Section::make('Brand Design Guidelines')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\ColorPicker::make('primary_color')
                                    ->label('Primary Color')
                                    ->helperText('Main brand color'),
                                Forms\Components\ColorPicker::make('secondary_color')
                                    ->label('Secondary Color')
                                    ->helperText('Supporting brand color'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('font_family')
                                    ->label('Body Font Family')
                                    ->placeholder('e.g., Arial, Helvetica, sans-serif')
                                    ->helperText('Primary font for body text'),
                                Forms\Components\TextInput::make('heading_font_family')
                                    ->label('Heading Font Family')
                                    ->placeholder('e.g., Georgia, serif')
                                    ->helperText('Font for headings and titles'),
                            ]),
                    ]),
                
                Forms\Components\Section::make('IT Configuration')
                    ->description('Information displayed to IT team during setup')
                    ->schema([
                        Forms\Components\Repeater::make('it_configuration')
                            ->label('IT Setup Instructions')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('Field Label')
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Description/Instructions')
                                    ->required()
                                    ->rows(3),
                                Forms\Components\TextInput::make('example')
                                    ->label('Example Value')
                                    ->helperText('Optional example to show IT team'),
                            ])
                            ->columns(1)
                            ->defaultItems(0)
                            ->addActionLabel('Add IT Instruction')
                            ->collapsible()
                            ->cloneable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                
                Forms\Components\Section::make('Example Images')
                    ->description('Upload reference images for various sections')
                    ->schema([
                        Forms\Components\Repeater::make('example_images')
                            ->label(false)
                            ->schema([
                                Forms\Components\TextInput::make('section_key')
                                    ->label('Section Key')
                                    ->required()
                                    ->placeholder('e.g., room_details_toggle')
                                    ->helperText('Identifier for this example (e.g., room_details_toggle, email_template)'),
                                Forms\Components\FileUpload::make('image')
                                    ->label('Example Image')
                                    ->image()
                                    ->required()
                                    ->imagePreviewHeight('150')
                                    ->directory('brand-examples')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                    ->helperText('Upload the example image for this section'),
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(2)
                                    ->placeholder('Brief description of when/how this example is used'),
                            ])
                            ->addActionLabel('Add Example Image')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['section_key']) ? ucfirst(str_replace('_', ' ', $state['section_key'])) : 'New Example'
                            )
                            ->defaultItems(0)
                            ->cloneable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Forms\Components\Section::make('Promotions')
                    ->description('Configure promotion types available for this brand')
                    ->schema([
                        Forms\Components\Repeater::make('promotions')
                            ->label(false)
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Promotion Type')
                                    ->options([
                                        'promotion' => 'Promotion',
                                        'promotion_tiles' => 'Promotion Tiles',
                                        'concierge_perfect_day' => 'Concierge Perfect Day',
                                    ])
                                    ->required(),
                                Forms\Components\FileUpload::make('example_image')
                                    ->label('Example Image')
                                    ->image()
                                    ->imagePreviewHeight('150')
                                    ->directory('promotion-examples')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                    ->helperText('Upload an example image for this promotion type'),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Toggle::make('show_title')
                                            ->label('Show Title'),
                                        Forms\Components\Toggle::make('show_image')
                                            ->label('Show Image'),
                                        Forms\Components\Toggle::make('show_icon')
                                            ->label('Show Icon'),
                                        Forms\Components\Toggle::make('show_url')
                                            ->label('Show URL'),
                                        Forms\Components\Toggle::make('show_button')
                                            ->label('Show Button Text'),
                                        Forms\Components\Toggle::make('show_text')
                                            ->label('Show Text'),
                                    ])
                            ])
                            ->addActionLabel('Add Promotion Type')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['type']) ? ucfirst(str_replace('_', ' ', $state['type'])) : 'New Promotion'
                            )
                            ->cloneable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Forms\Components\Section::make('Template Examples')
                    ->description('Configure template examples for different modules')
                    ->schema([
                        Forms\Components\Repeater::make('template_examples')
                            ->label(false)
                            ->schema([
                                Forms\Components\Select::make('module_id')
                                    ->label('Module')
                                    ->options(Module::pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),
                                Forms\Components\FileUpload::make('template_image')
                                    ->label('Template Example Image')
                                    ->image()
                                    ->imagePreviewHeight('150')
                                    ->directory('template-examples')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                    ->helperText('Upload an example template for this module'),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Implementation Notes')
                                    ->rows(3)
                                    ->placeholder('Any special instructions or notes for this template'),
                            ])
                            ->addActionLabel('Add Template Example')
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                if (!isset($state['module_id'])) {
                                    return 'New Template';
                                }
                                return \App\Models\Module::find($state['module_id'])?->name;
                            })
                            ->cloneable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Forms\Components\Section::make('Datastore Configuration')
                    ->description('Configure which datastore tables are required for this brand')
                    ->schema([
                        Forms\Components\CheckboxList::make('datastore_tables')
                            ->label('Brand-Specific Datastore Tables')
                            ->options(static::getDatastoreTableOptions())
                            ->columns(3)
                            ->helperText('Select tables that should be enabled when this brand is selected. These are in addition to standard and module-specific tables.')
                            ->searchable()
                            ->bulkToggleable(),
                            
                        Forms\Components\Repeater::make('custom_datastore_tables')
                            ->label('Custom Datastore Tables')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Group::make([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Table Name')
                                                ->required()
                                                ->placeholder('e.g., membershipLevels')
                                                ->helperText('Internal table identifier (camelCase)'),
                                            Forms\Components\TextInput::make('label')
                                                ->label('Display Label')
                                                ->required()
                                                ->placeholder('e.g., Membership Levels'),
                                            Forms\Components\Select::make('icon')
                                                ->label('Icon')
                                                ->options(FontAwesomeIcons::getV4Icons())
                                                ->searchable()
                                                ->required()
                                                ->allowHtml()
                                                ->helperText('Select a FontAwesome v4 icon'),
                                            Forms\Components\Textarea::make('description')
                                                ->label('Description')
                                                ->rows(3)
                                                ->placeholder('Brief description of what this table stores'),
                                        ])->columnSpan(1),
                                        
                                        Forms\Components\Group::make([
                                            Forms\Components\Repeater::make('fields')
                                    ->label('Table Fields')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Field Name')
                                            ->required()
                                            ->placeholder('e.g., code')
                                            ->helperText('Internal field identifier'),
                                        Forms\Components\Select::make('type')
                                            ->label('Field Type')
                                            ->required()
                                            ->options([
                                                'text' => 'Text',
                                                'textarea' => 'Textarea',
                                                'number' => 'Number',
                                                'select' => 'Select (Dropdown)',
                                                'boolean' => 'Boolean (Yes/No)',
                                                'date' => 'Date',
                                                'datetime' => 'Date & Time',
                                                'file' => 'File Upload',
                                                'image' => 'Image Upload',
                                                'summernote' => 'Rich Text Editor',
                                                'json' => 'JSON Data',
                                                'relation' => 'Relation to Another Table',
                                            ])
                                            ->reactive(),
                                        Forms\Components\TextInput::make('label')
                                            ->label('Field Label')
                                            ->required()
                                            ->placeholder('e.g., Membership Level'),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Field Description')
                                            ->rows(2),
                                        Forms\Components\Toggle::make('required')
                                            ->label('Required Field')
                                            ->default(false),
                                        Forms\Components\Toggle::make('translate')
                                            ->label('Translatable')
                                            ->default(false),
                                        Forms\Components\Toggle::make('showInList')
                                            ->label('Show in List View')
                                            ->default(true),
                                        Forms\Components\KeyValue::make('options')
                                            ->label('Options (for Select fields)')
                                            ->keyLabel('Value')
                                            ->valueLabel('Display Label')
                                            ->addActionLabel('Add Option')
                                            ->visible(fn ($get) => $get('type') === 'select'),
                                        Forms\Components\TextInput::make('default')
                                            ->label('Default Value')
                                            ->visible(fn ($get) => in_array($get('type'), ['text', 'number', 'boolean'])),
                                        Forms\Components\TextInput::make('relation_table')
                                            ->label('Related Table')
                                            ->placeholder('e.g., membershipLevels')
                                            ->visible(fn ($get) => $get('type') === 'relation'),
                                        Forms\Components\TextInput::make('relation_display')
                                            ->label('Display Field from Related Table')
                                            ->placeholder('e.g., code')
                                            ->visible(fn ($get) => $get('type') === 'relation'),
                                    ])
                                    ->addActionLabel('Add Field')
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => 
                                        isset($state['label']) ? $state['label'] : 'New Field'
                                    ),
                                        ])->columnSpan(1),
                                    ]),
                            ])
                            ->addActionLabel('Add Custom Table')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['label']) ? $state['label'] : 'New Table'
                            )
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->width(60)
                    ->height(40),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('hotelChain.name')
                    ->label('Hotel Chain')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ColorColumn::make('primary_color')
                    ->label('Brand Colors')
                    ->tooltip(fn ($record) => "Primary: {$record->primary_color}, Secondary: {$record->secondary_color}"),
                Tables\Columns\TextColumn::make('datastore_tables')
                    ->label('Brand Tables')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) : 0)
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('custom_datastore_tables')
                    ->label('Custom Tables')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) : 0)
                    ->badge()
                    ->color('warning'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('hotel_chain')
                    ->relationship('hotelChain', 'name')
                    ->preload()
                    ->searchable(),
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
            'index' => Pages\ListHotelBrands::route('/'),
            'create' => Pages\CreateHotelBrand::route('/create'),
            'edit' => Pages\EditHotelBrand::route('/{record}/edit'),
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