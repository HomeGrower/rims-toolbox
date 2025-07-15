<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProjectResource\Pages;
use App\Filament\Admin\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use App\Models\HotelChain;
use App\Models\HotelBrand;
use App\Models\PmsType;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Project Overview')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('hotel_name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Hotel Name'),
                                Forms\Components\Select::make('project_type')
                                    ->label('Type')
                                    ->options([
                                        'installation' => 'Installation',
                                        'upgrade' => 'Upgrade',
                                        'single_template' => 'Single Template',
                                    ])
                                    ->required()
                                    ->default('installation')
                                    ->native(false),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'setup' => 'Setup',
                                        'active' => 'Active',
                                        'completed' => 'Completed',
                                        'paused' => 'Paused',
                                    ])
                                    ->required()
                                    ->default('setup'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('access_code')
                                    ->label('Access Code')
                                    ->maxLength(8)
                                    ->default(fn () => Project::generateUniqueCode())
                                    ->unique(ignoreRecord: true)
                                    ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                                    ->disabled(fn ($livewire) => $livewire instanceof Pages\EditProject)
                                    ->helperText('Auto-generated access code'),
                                Forms\Components\TextInput::make('name')
                                    ->maxLength(255)
                                    ->label('Internal Reference')
                                    ->placeholder('Uses hotel name if empty')
                                    ->helperText('Leave empty to use hotel name')
                                    ->nullable()
                                    ->dehydrateStateUsing(fn ($state) => empty($state) ? null : $state),
                            ]),
                    ]),
                
                Forms\Components\Section::make('Hotel Chain & Brand')
                    ->schema([
                        Forms\Components\Grid::make(3)
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
                                Forms\Components\Select::make('pms_type_id')
                                    ->label('PMS Type')
                                    ->options(PmsType::active()->ordered()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->helperText('Select the property management system'),
                            ]),
                    ]),
                
                Forms\Components\Section::make('Languages')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('primary_language')
                                    ->label('Primary Language')
                                    ->options(fn () => \App\Models\Language::active()->ordered()->pluck('name', 'code')->toArray())
                                    ->default('en')
                                    ->required()
                                    ->searchable(),
                                Forms\Components\Select::make('languages')
                                    ->label('Additional Languages')
                                    ->options(fn () => \App\Models\Language::active()->ordered()->pluck('name', 'code')->toArray())
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Select all languages the hotel supports')
                                    ->dehydrateStateUsing(function ($state, Forms\Get $get) {
                                        // Ensure primary language is not in additional languages
                                        $primary = $get('primary_language');
                                        if (is_array($state) && $primary && in_array($primary, $state)) {
                                            $state = array_diff($state, [$primary]);
                                        }
                                        return array_values($state);
                                    }),
                            ]),
                    ]),
                
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                        Forms\Components\TagsInput::make('notification_emails')
                            ->label('Notification Email Addresses')
                            ->placeholder('Add email address and press Enter')
                            ->helperText('Email addresses to receive the access code (optional)')
                            ->suggestions([])
                            ->splitKeys(['Tab', ','])
                            ->reorderable(),
                        Forms\Components\DateTimePicker::make('activated_at')
                            ->label('Activation Date')
                            ->displayFormat('M d, Y H:i'),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completion Date')
                            ->displayFormat('M d, Y H:i'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Delegation')
                    ->schema([
                        Forms\Components\Select::make('delegated_to')
                            ->label('Delegate to Admin')
                            ->options(
                                User::where('role', 'admin')
                                    ->where('id', '!=', auth()->id())
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->nullable()
                            ->helperText('Assign this project to another administrator'),
                        Forms\Components\Placeholder::make('delegated_info')
                            ->label('Delegation Status')
                            ->content(fn ($record) => 
                                $record && $record->isDelegated() && $record->delegated_at
                                    ? "Delegated to {$record->delegatedTo->name} on " . $record->delegated_at->format('M d, Y')
                                    : 'Not delegated'
                            )
                            ->visible(fn ($livewire) => $livewire instanceof Pages\EditProject),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hotel_name')
                    ->label('Project Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('project_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'installation' => 'success',
                        'upgrade' => 'warning',
                        'single_template' => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('hotelChain.name')
                    ->label('Chain')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('hotelBrand.name')
                    ->label('Brand')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('access_code')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Access code copied!')
                    ->badge(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'setup',
                        'success' => 'active',
                        'info' => 'completed',
                        'warning' => 'paused',
                    ]),
                Tables\Columns\TextColumn::make('languages_display')
                    ->label('Languages')
                    ->getStateUsing(function ($record) {
                        $allLanguages = [];
                        
                        // Add primary language
                        if ($record->primary_language) {
                            $allLanguages[] = $record->primary_language;
                        }
                        
                        // Add additional languages
                        if (is_array($record->languages) && count($record->languages) > 0) {
                            $allLanguages = array_merge($allLanguages, $record->languages);
                        }
                        
                        // Remove duplicates
                        $allLanguages = array_unique($allLanguages);
                        
                        if (empty($allLanguages)) {
                            return '-';
                        }
                        
                        // Get language names
                        $languageNames = \App\Models\Language::whereIn('code', $allLanguages)
                            ->pluck('name', 'code')
                            ->toArray();
                        
                        // Map codes to names
                        $displayNames = array_map(function($code) use ($languageNames, $record) {
                            $name = $languageNames[$code] ?? $code;
                            return $code === $record->primary_language ? $name . ' (Primary)' : $name;
                        }, $allLanguages);
                        
                        // Show first 2 languages and count of others
                        if (count($displayNames) > 2) {
                            $first = array_slice($displayNames, 0, 2);
                            $remaining = count($displayNames) - 2;
                            return implode(', ', $first) . " +{$remaining}";
                        }
                        
                        return implode(', ', $displayNames);
                    })
                    ->searchable(false)
                    ->toggleable(),
                Tables\Columns\ViewColumn::make('overall_progress')
                    ->label('Progress')
                    ->view('filament.tables.columns.progress-bar')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('activated_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'setup' => 'Setup',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'paused' => 'Paused',
                    ]),
            ])
            ->actions([])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ModulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'create-wizard' => Pages\CreateProjectWizard::route('/create-wizard'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
            'datastore-builder' => Pages\DatastoreBuilder::route('/{record}/datastore-builder'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['creator', 'modules', 'hotelChain', 'hotelBrand', 'pmsType', 'delegatedTo']);
            
        // Super admins see all projects
        if (auth()->user()->isSuperAdmin()) {
            return $query;
        }
        
        // Regular admins see projects they created OR projects delegated to them
        return $query->where(function ($q) {
            $q->where('created_by', auth()->id())
              ->orWhere('delegated_to', auth()->id());
        });
    }
}