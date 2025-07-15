<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Filament\Admin\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?string $navigationLabel = 'Admin Users';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $modelLabel = 'Admin User';
    
    protected static ?string $pluralModelLabel = 'Admin Users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('role')
                            ->options([
                                'admin' => 'Administrator',
                                'user' => 'Standard User',
                            ])
                            ->default('admin')
                            ->required()
                            ->native(false)
                            ->helperText('Only administrators can access this admin panel'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Security')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn ($livewire) => $livewire instanceof Pages\CreateUser)
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn ($state) => $state ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->revealable()
                            ->helperText(fn ($livewire) => $livewire instanceof Pages\EditUser ? 'Leave empty to keep current password' : null),
                        Forms\Components\Toggle::make('email_verified_at')
                            ->label('Email Verified')
                            ->dehydrateStateUsing(fn ($state) => $state ? now() : null)
                            ->afterStateHydrated(fn ($component, $state) => $component->state($state !== null)),
                        Forms\Components\Toggle::make('is_super_admin')
                            ->label('Super Administrator')
                            ->helperText('Super admins can see and manage all projects from all admins')
                            ->visible(fn () => auth()->user()->isSuperAdmin()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'success',
                        'user' => 'gray',
                    })
                    ->formatStateUsing(fn ($record): string => 
                        $record->is_super_admin 
                            ? 'Super Admin' 
                            : match ($record->role) {
                                'admin' => 'Administrator',
                                'user' => 'Standard User',
                            }
                    ),
                Tables\Columns\TextColumn::make('projects_count')
                    ->counts('projects')
                    ->label('Projects')
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Administrator',
                        'user' => 'Standard User',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'admin'));
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
