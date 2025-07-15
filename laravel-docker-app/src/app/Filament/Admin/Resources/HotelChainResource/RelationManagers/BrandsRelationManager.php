<?php

namespace App\Filament\Admin\Resources\HotelChainResource\RelationManagers;

use App\Models\Module;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BrandsRelationManager extends RelationManager
{
    protected static string $relationship = 'brands';
    
    protected static ?string $title = 'Hotel Brands';

    public function form(Form $form): Form
    {
        // Form is handled by HotelBrandResource
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->width(40)
                    ->height(40)
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->weight('bold')
                    ->size('sm'),
                Tables\Columns\TextColumn::make('code')
                    ->badge(),
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
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active brands')
                    ->falseLabel('Inactive brands')
                    ->native(false),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Brand')
                    ->url(fn () => \App\Filament\Admin\Resources\HotelBrandResource::getUrl('create', [
                        'hotel_chain_id' => $this->ownerRecord->id
                    ])),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn ($record) => \App\Filament\Admin\Resources\HotelBrandResource::getUrl('edit', ['record' => $record])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->reorderable('sort_order')
            ->paginated(false);
    }
}