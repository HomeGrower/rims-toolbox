<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\HotelChainResource\Pages;
use App\Filament\Admin\Resources\HotelChainResource\RelationManagers;
use App\Helpers\FontAwesomeIcons;
use App\Models\HotelChain;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class HotelChainResource extends Resource
{
    protected static ?string $model = HotelChain::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    
    protected static ?string $navigationGroup = 'System Configuration';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $modelLabel = 'Hotel Chain';
    
    protected static ?string $pluralModelLabel = 'Hotel Chains';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Chain Information')
                    ->schema([
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
                            ->helperText('Unique chain code'),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Datastore Configuration')
                    ->description('Configure custom datastore tables for this hotel chain')
                    ->schema([
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('brands_count')
                    ->counts('brands')
                    ->label('Brands')
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
            RelationManagers\BrandsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHotelChains::route('/'),
            'create' => Pages\CreateHotelChain::route('/create'),
            'edit' => Pages\EditHotelChain::route('/{record}/edit'),
        ];
    }
}