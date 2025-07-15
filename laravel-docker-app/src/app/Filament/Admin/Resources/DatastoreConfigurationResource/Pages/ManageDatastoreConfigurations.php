<?php

namespace App\Filament\Admin\Resources\DatastoreConfigurationResource\Pages;

use App\Filament\Admin\Resources\DatastoreConfigurationResource;
use App\Models\Setting;
use App\Models\DatastoreStructureVersion;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\HtmlString;

class ManageDatastoreConfigurations extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static string $resource = DatastoreConfigurationResource::class;
    
    protected static string $view = 'filament.admin.resources.datastore-configuration-resource.pages.manage-datastore-configurations';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'standard_datastore_tables' => Setting::get('standard_datastore_tables', [
                'properties',
                'rooms',
                'roomCategories',
                'buildings',
                'taxes',
                'cancellationPolicies',
                'colors',
                'tagMapping'
            ]),
            'default_datastore_structure' => Setting::get('default_datastore_structure', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Default Datastore Structure')
                    ->description('Upload the default datastore structure JSON file that will be used as the base reference')
                    ->schema([
                        Placeholder::make('current_version')
                            ->label('Current Active Version')
                            ->content(function () {
                                $active = DatastoreStructureVersion::getActive();
                                if ($active) {
                                    return new HtmlString(sprintf(
                                        '<div class="flex items-center space-x-4">
                                            <span class="text-lg font-medium">%s</span>
                                            <span class="text-sm text-gray-500">Uploaded by %s on %s</span>
                                        </div>',
                                        $active->version,
                                        $active->uploaded_by,
                                        $active->created_at->format('Y-m-d H:i:s')
                                    ));
                                }
                                return 'No version uploaded yet';
                            }),
                            
                        FileUpload::make('default_datastore_upload')
                            ->label('Upload New Version')
                            ->acceptedFileTypes(['application/json'])
                            ->helperText('Upload a new Datastore_Structure.json file. This will create a new version and activate it.')
                    ]),
                    
                Section::make('Standard Datastore Tables')
                    ->description('Configure which datastore tables should be available by default for all projects')
                    ->schema([
                        CheckboxList::make('standard_datastore_tables')
                            ->label('Available Tables')
                            ->options($this->getDatastoreTableOptions())
                            ->columns(3)
                            ->helperText('These tables will be available by default for all projects. Additional tables can be enabled per module.')
                            ->searchable()
                            ->bulkToggleable()
                    ])
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        // Save standard datastore tables
        Setting::set('standard_datastore_tables', $data['standard_datastore_tables'], 'json', 'datastore');
        
        // Handle file upload if present
        if (!empty($data['default_datastore_upload'])) {
            try {
                // Get the uploaded file name from form state
                $uploadedFile = $this->form->getRawState()['default_datastore_upload'];
                $originalName = 'datastore_structure.json';
                
                if (is_object($uploadedFile) && method_exists($uploadedFile, 'getClientOriginalName')) {
                    $originalName = $uploadedFile->getClientOriginalName();
                }
                
                // Get the temporary uploaded file path
                $filePath = storage_path('app/livewire-tmp/' . $data['default_datastore_upload']);
                if (!file_exists($filePath)) {
                    $filePath = storage_path('app/public/' . $data['default_datastore_upload']);
                }
                
                $content = file_get_contents($filePath);
                
                // Remove BOM if present
                $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
                
                $json = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Create version record
                    $version = DatastoreStructureVersion::createFromUpload(
                        $originalName,
                        json_encode($json, JSON_PRETTY_PRINT)
                    );
                    
                    // Also save to settings for backward compatibility
                    Setting::set('default_datastore_structure', json_encode($json, JSON_PRETTY_PRINT), 'text', 'datastore');
                    
                    Notification::make()
                        ->title('Datastore structure uploaded successfully')
                        ->body('Version ' . $version->version . ' is now active')
                        ->success()
                        ->send();
                        
                    // Refresh the form
                    $this->mount();
                } else {
                    throw new \Exception('Invalid JSON: ' . json_last_error_msg());
                }
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Error uploading file')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
                return;
            }
        }
        
        Notification::make()
            ->title('Datastore Configuration saved successfully')
            ->success()
            ->send();
    }
    
    protected function getDatastoreTableOptions(): array
    {
        return [
            'attachments' => 'Attachments',
            'buildings' => 'Buildings',
            'calendar' => 'Calendar',
            'cancellationPolicies' => 'Cancellation Policies',
            'cancellationReasons' => 'Cancellation Reasons',
            'colors' => 'Colors',
            'depositPolicies' => 'Deposit Policies',
            'extraCategories' => 'Extra Categories',
            'extras' => 'Extras',
            'extrasItems' => 'Extras Items',
            'fixedCharges' => 'Fixed Charges',
            'greetings' => 'Greetings',
            'guaranteeTypes' => 'Guarantee Types',
            'infos' => 'Infos',
            'landingpages' => 'Landing Pages',
            'links' => 'Links',
            'mealplans' => 'Meal Plans',
            'membershipPrograms' => 'Membership Programs',
            'memberships' => 'Memberships',
            'operaTransactionCodes' => 'Opera Transaction Codes',
            'packages' => 'Packages',
            'partnerLinks' => 'Partner Links',
            'paymentInterfaceOptions' => 'Payment Interface Options',
            'paymentMethods' => 'Payment Methods',
            'periodes' => 'Periods',
            'preferenceValues' => 'Preference Values',
            'preferences' => 'Preferences',
            'promotions' => 'Promotions',
            'properties' => 'Properties',
            'rates' => 'Rates',
            'roomCategories' => 'Room Categories',
            'roomFeatures' => 'Room Features',
            'roomTypes' => 'Room Types',
            'roomUpsell' => 'Room Upsell',
            'rooms' => 'Rooms',
            'seasons' => 'Seasons',
            'socialMedia' => 'Social Media',
            'specialRequests' => 'Special Requests',
            'tagMapping' => 'Tag Mapping',
            'tags' => 'Tags',
            'taxes' => 'Taxes',
            'templates' => 'Templates',
            'transferStations' => 'Transfer Stations',
            'transfers' => 'Transfers',
            'users' => 'Users',
            'wordings' => 'Wordings',
        ];
    }
    
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(DatastoreStructureVersion::query())
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('version')
                    ->label('Version')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('filename')
                    ->label('File Name')
                    ->searchable(),
                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB')
                    ->sortable(),
                TextColumn::make('uploaded_by')
                    ->label('Uploaded By')
                    ->searchable(),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Upload Date')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->actions([
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn ($record) => !$record->is_active)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Deactivate all versions
                        DatastoreStructureVersion::where('is_active', true)->update(['is_active' => false]);
                        
                        // Activate this version
                        $record->update(['is_active' => true]);
                        
                        // Update settings
                        Setting::set('default_datastore_structure', $record->structure, 'text', 'datastore');
                        
                        Notification::make()
                            ->title('Version activated')
                            ->body('Version ' . $record->version . ' is now active')
                            ->success()
                            ->send();
                    }),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($record) {
                        return response()->streamDownload(
                            function () use ($record) {
                                echo $record->structure;
                            },
                            'datastore_structure_' . $record->version . '.json',
                            ['Content-Type' => 'application/json']
                        );
                    }),
            ])
            ->paginated([10, 25, 50]);
    }
}
