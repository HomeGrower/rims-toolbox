<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ConditionResource\Pages;
use App\Models\Condition;
use App\Models\HotelChain;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ConditionResource extends Resource
{
    protected static ?string $model = Condition::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?string $navigationGroup = 'General Settings';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Children, VIP Member'),
                            
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., children, vip_member')
                            ->helperText('Unique identifier for the condition'),
                    ]),
                    
                Forms\Components\TextInput::make('description')
                    ->maxLength(255)
                    ->placeholder('Brief description of when this condition applies'),
                    
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'general' => 'General (All Chains)',
                                'chain_specific' => 'Chain Specific',
                            ])
                            ->default('general')
                            ->reactive()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                $state === 'general' ? $set('hotel_chain_id', null) : null
                            ),
                            
                        Forms\Components\Select::make('hotel_chain_id')
                            ->label('Hotel Chain')
                            ->options(HotelChain::query()->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (Forms\Get $get): bool => $get('type') === 'chain_specific')
                            ->required(fn (Forms\Get $get): bool => $get('type') === 'chain_specific'),
                    ]),
                    
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
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
                    ->copyable()
                    ->copyMessage('Code copied'),
                    
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'success',
                        'chain_specific' => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'general' => 'General',
                        'chain_specific' => 'Chain Specific',
                    }),
                    
                Tables\Columns\TextColumn::make('hotelChain.name')
                    ->label('Hotel Chain')
                    ->placeholder('All Chains')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'general' => 'General',
                        'chain_specific' => 'Chain Specific',
                    ]),
                    
                Tables\Filters\SelectFilter::make('hotel_chain_id')
                    ->label('Hotel Chain')
                    ->options(HotelChain::query()->pluck('name', 'id'))
                    ->searchable(),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListConditions::route('/'),
            'create' => Pages\CreateCondition::route('/create'),
            'edit' => Pages\EditCondition::route('/{record}/edit'),
        ];
    }
}