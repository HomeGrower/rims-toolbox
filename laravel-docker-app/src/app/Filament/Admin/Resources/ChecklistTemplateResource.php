<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ChecklistTemplateResource\Pages;
use App\Filament\Admin\Resources\ChecklistTemplateResource\RelationManagers;
use App\Models\ChecklistTemplate;
use App\Models\Module;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChecklistTemplateResource extends Resource
{
    protected static ?string $model = ChecklistTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    // Hide from navigation
    protected static bool $shouldRegisterNavigation = false;
    
    protected static ?string $navigationLabel = 'Checklist Questions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Question Details')
                    ->schema([
                        Forms\Components\TextInput::make('question')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('category')
                            ->required()
                            ->options([
                                'property-info' => 'Property Information',
                                'guest-services' => 'Guest Services',
                                'operations' => 'Operations',
                                'distribution' => 'Distribution',
                                'technology' => 'Technology',
                                'finance' => 'Finance',
                            ]),
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'boolean' => 'Yes/No',
                                'text' => 'Text Input',
                                'select' => 'Single Select',
                                'multiselect' => 'Multiple Select',
                                'number' => 'Number',
                            ])
                            ->reactive(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Options')
                    ->schema([
                        Forms\Components\TagsInput::make('options')
                            ->helperText('Enter options for select/multiselect questions')
                            ->required(fn (callable $get) => in_array($get('type'), ['select', 'multiselect'])),
                    ])
                    ->visible(fn (callable $get) => in_array($get('type'), ['select', 'multiselect'])),
                    
                Forms\Components\Section::make('Module Mappings')
                    ->schema([
                        Forms\Components\Repeater::make('module_mappings')
                            ->schema([
                                Forms\Components\TextInput::make('condition')
                                    ->label('Answer/Condition')
                                    ->helperText('e.g., "true", "1-50 rooms", "multiple"')
                                    ->required(),
                                Forms\Components\Select::make('modules')
                                    ->label('Modules to Enable')
                                    ->multiple()
                                    ->options(Module::pluck('name', 'slug'))
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Add Mapping'),
                    ]),
                    
                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\Toggle::make('is_required')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\BadgeColumn::make('category')
                    ->colors([
                        'primary' => 'property-info',
                        'success' => 'guest-services',
                        'warning' => 'operations',
                        'info' => 'distribution',
                        'danger' => 'technology',
                        'gray' => 'finance',
                    ]),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'boolean',
                        'success' => 'text',
                        'warning' => 'select',
                        'info' => 'multiselect',
                        'danger' => 'number',
                    ]),
                Tables\Columns\IconColumn::make('is_required')
                    ->boolean()
                    ->label('Required'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'property-info' => 'Property Information',
                        'guest-services' => 'Guest Services',
                        'operations' => 'Operations',
                        'distribution' => 'Distribution',
                        'technology' => 'Technology',
                        'finance' => 'Finance',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'boolean' => 'Yes/No',
                        'text' => 'Text Input',
                        'select' => 'Single Select',
                        'multiselect' => 'Multiple Select',
                        'number' => 'Number',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
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
            'index' => Pages\ListChecklistTemplates::route('/'),
            'create' => Pages\CreateChecklistTemplate::route('/create'),
            'edit' => Pages\EditChecklistTemplate::route('/{record}/edit'),
        ];
    }
}