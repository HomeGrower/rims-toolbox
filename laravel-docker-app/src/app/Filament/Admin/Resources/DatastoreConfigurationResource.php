<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DatastoreConfigurationResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

class DatastoreConfigurationResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?string $navigationLabel = 'Datastore Configuration';
    
    protected static ?string $modelLabel = 'Datastore Configuration';
    
    protected static ?string $pluralModelLabel = 'Datastore Configuration';
    
    protected static ?int $navigationSort = 101;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDatastoreConfigurations::route('/'),
        ];
    }
}
