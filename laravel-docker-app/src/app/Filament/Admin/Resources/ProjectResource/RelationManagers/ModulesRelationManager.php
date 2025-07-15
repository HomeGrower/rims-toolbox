<?php

namespace App\Filament\Admin\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ModulesRelationManager extends RelationManager
{
    protected static string $relationship = 'modules';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('module_id')
                    ->relationship('modules', 'name')
                    ->required()
                    ->preload()
                    ->searchable(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'blocked' => 'Blocked',
                    ])
                    ->default('pending')
                    ->required(),
                Forms\Components\TextInput::make('progress')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('pivot.status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'blocked',
                    ]),
                Tables\Columns\TextColumn::make('pivot.progress')
                    ->label('Progress')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pivot.started_at')
                    ->label('Started')
                    ->dateTime('M d, Y')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('pivot.completed_at')
                    ->label('Completed')
                    ->dateTime('M d, Y')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'blocked' => 'Blocked',
                    ])
                    ->attribute('pivot.status'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'blocked' => 'Blocked',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\TextInput::make('progress')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form(fn (Tables\Actions\EditAction $action): array => [
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'blocked' => 'Blocked',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('progress')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),
                    ]),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}