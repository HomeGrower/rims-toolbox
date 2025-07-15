<?php

namespace App\Filament\Admin\Resources\SettingResource\Pages;

use App\Filament\Admin\Resources\SettingResource;
use App\Models\Setting;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SettingResource::class;
    
    protected static string $view = 'filament.admin.resources.setting-resource.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'rims_logo' => Setting::where('key', 'rims_logo')->first()?->value,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('RIMS Branding')
                    ->description('Configure the default RIMS logo that appears when no brand logo is set')
                    ->schema([
                        FileUpload::make('rims_logo')
                            ->label('RIMS Logo')
                            ->image()
                            ->directory('settings')
                            ->visibility('public')
                            ->imagePreviewHeight('200')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'])
                            ->helperText('This logo will be displayed when hotels have not uploaded their brand logo')
                            ->maxSize(2048)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        // Delete old logo if exists
        $oldLogo = Setting::where('key', 'rims_logo')->first();
        if ($oldLogo && $oldLogo->value && $oldLogo->value !== $data['rims_logo']) {
            Storage::disk('public')->delete($oldLogo->value);
        }
        
        Setting::set('rims_logo', $data['rims_logo'], 'image', 'branding');
        
        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}