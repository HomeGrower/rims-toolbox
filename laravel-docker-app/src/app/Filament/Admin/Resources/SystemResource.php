<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SystemResource\Pages;
use Filament\Resources\Resource;

class SystemResource extends Resource
{
    protected static ?string $model = null;
    
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?string $slug = 'system';
    
    protected static ?int $navigationSort = 100;

    public static function getPages(): array
    {
        return [
            'index' => Pages\LogViewer::route('/'),
            'backups' => Pages\BackupManager::route('/backups'),
        ];
    }
    
    public static function getNavigationLabel(): string
    {
        return 'System';
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // We use custom navigation items instead
    }
}