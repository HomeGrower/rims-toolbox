<?php

namespace App\Filament\Admin\Resources\ProjectResource\Pages;

use App\Filament\Admin\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Support\HtmlString;
use App\Models\Language;
use App\Models\ProjectData;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;
    
    // Enable polling for real-time updates
    protected function getPollingInterval(): ?string
    {
        return '5s'; // Refresh every 5 seconds
    }
    
    public function getHeading(): string
    {
        return $this->record->hotel_name;
    }
    

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('datastore-builder')
                ->label('Datastore Builder')
                ->icon('heroicon-o-code-bracket-square')
                ->color('info')
                ->url(fn () => ProjectResource::getUrl('datastore-builder', ['record' => $this->record])),
            Actions\Action::make('view-client')
                ->label('View as Client')
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->url(fn () => route('client.dashboard') . '?access_code=' . $this->record->access_code)
                ->openUrlInNewTab(),
            Actions\Action::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return $this->exportProjectData();
                }),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Components\Section::make('Project Overview')
                ->headerActions([
                    Components\Actions\Action::make('status')
                        ->label(ucfirst($this->record->status))
                        ->badge()
                        ->color(match($this->record->status) {
                            'setup' => 'warning',
                            'active' => 'success',
                            'completed' => 'info',
                            'paused' => 'danger',
                            default => 'gray'
                        })
                        ->disabled(),
                    Components\Actions\Action::make('access_code')
                        ->label($this->record->access_code)
                        ->color('gray')
                        ->action(function () {
                            $this->js("
                                navigator.clipboard.writeText('{$this->record->access_code}');
                                \$tooltip('Access code copied!', { timeout: 2000 });
                            ");
                        })
                        ->extraAttributes(['class' => 'font-mono text-lg'])
                ])
                ->schema([
                    Components\Grid::make([
                        'default' => 1,
                        'sm' => 2,
                        'md' => 4,
                    ])
                        ->schema([
                            Components\TextEntry::make('chain_brand')
                                ->label('Chain & Brand')
                                ->getStateUsing(fn ($record) => 
                                    ($record->hotelChain?->name ?? 'N/A') . ' / ' . ($record->hotelBrand?->name ?? 'N/A')
                                ),
                            Components\TextEntry::make('project_type')
                                ->label('Project Type')
                                ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state)))
                                ->badge()
                                ->color('primary'),
                            Components\TextEntry::make('delegatedAdmin.name')
                                ->label('Delegated Admin')
                                ->default('Not assigned')
                                ->icon('heroicon-o-user'),
                            Components\TextEntry::make('overall_progress')
                                ->label('Progress')
                                ->suffix('%')
                                ->size('lg')
                                ->weight('bold')
                                ->color(fn ($state) => match(true) {
                                    $state >= 80 => 'success',
                                    $state >= 50 => 'warning',
                                    default => 'danger'
                                }),
                        ]),
                    Components\Group::make([
                            Components\Grid::make(4)
                                ->schema([
                                    Components\TextEntry::make('pmsType.name')
                                        ->label('PMS System')
                                        ->default('Not assigned'),
                                    Components\TextEntry::make('languages')
                                        ->label('Languages')
                                        ->badge()
                                        ->getStateUsing(function ($record) {
                                            $allLanguages = [];
                                            
                                            // Add primary language if exists
                                            if ($record->primary_language) {
                                                $allLanguages[] = $record->primary_language;
                                            }
                                            
                                            // Add additional languages if exist
                                            if (is_array($record->languages) && count($record->languages) > 0) {
                                                $allLanguages = array_merge($allLanguages, $record->languages);
                                            }
                                            
                                            // Remove duplicates and keep unique values
                                            $allLanguages = array_unique($allLanguages);
                                            
                                            if (empty($allLanguages)) {
                                                return ['Not selected'];
                                            }
                                            
                                            // Get language names from database
                                            $languages = Language::whereIn('code', $allLanguages)
                                                ->pluck('name', 'code')
                                                ->toArray();
                                            
                                            // Map codes to names, with primary language marked
                                            return array_map(function($code) use ($languages, $record) {
                                                $name = $languages[$code] ?? $code;
                                                if ($code === $record->primary_language) {
                                                    return $name . ' (Primary)';
                                                }
                                                return $name;
                                            }, $allLanguages);
                                        })
                                        ->separator(', '),
                                    Components\TextEntry::make('created_at')
                                        ->label('Created')
                                        ->date()
                                        ->tooltip(fn ($record) => $record->created_at->diffForHumans()),
                                    Components\TextEntry::make('modules_count')
                                        ->label('Modules')
                                        ->getStateUsing(fn ($record) => $record->modules->count())
                                        ->suffix(' selected'),
                                ]),
                            Components\Grid::make(3)
                                ->schema([
                                    Components\TextEntry::make('activated_at')
                                        ->label('Activation Date')
                                        ->dateTime('M d, Y')
                                        ->placeholder('Not activated'),
                                    Components\TextEntry::make('completed_at')
                                        ->label('Completion Date')
                                        ->dateTime('M d, Y')
                                        ->placeholder('Not completed'),
                                    Components\TextEntry::make('notification_emails')
                                        ->label('Notification Email Addresses')
                                        ->badge()
                                        ->separator(', ')
                                        ->default('No notification emails'),
                                ]),
                            Components\TextEntry::make('notes')
                                ->label('Notes')
                                ->visible(fn ($record) => !empty($record->notes))
                                ->columnSpanFull()
                                ->prose()
                    ])
                ]),
            
            // Image Downloads Section
            Components\Section::make('Image Downloads')
                ->description('Download project images individually or as ZIP archives')
                ->icon('heroicon-o-photo')
                ->collapsible()
                ->schema($this->getImageDownloadComponents())
        ]);
    }
    
    protected function exportProjectData()
    {
        $record = $this->record;
        $data = [];
        
        // Basic project info
        $data['Project Overview'] = [
            'Hotel Name' => $record->hotel_name,
            'Access Code' => $record->access_code,
            'Project Type' => ucfirst(str_replace('_', ' ', $record->project_type)),
            'Status' => $record->status,
            'Hotel Chain' => $record->hotelChain?->name,
            'Hotel Brand' => $record->hotelBrand?->name,
            'PMS Type' => $record->pmsType?->name,
            'Overall Progress' => $record->overall_progress . '%',
            'Created At' => $record->created_at->format('Y-m-d H:i:s'),
        ];
        
        // Collected data by team
        $groupedData = ProjectData::getGroupedDataForProject($record->id);
        foreach ($groupedData as $team => $sections) {
            $teamData = [];
            foreach ($sections as $section => $fields) {
                foreach ($fields as $field) {
                    $sectionName = $this->formatSectionName($section);
                    if (!isset($teamData[$sectionName])) {
                        $teamData[$sectionName] = [];
                    }
                    $teamData[$sectionName][$field->field_label] = $field->field_value ?: '-';
                }
            }
            if (!empty($teamData)) {
                $data[ucfirst($team) . ' Team Data'] = $teamData;
            }
        }
        
        // Export as JSON download
        $fileName = 'project-' . $record->access_code . '-data-' . date('Y-m-d-His') . '.json';
        
        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }
    
    protected function getImageDownloadComponents(): array
    {
        $record = $this->record;
        $components = [];
        
        // Get all image data for the project
        $logoImages = $this->getLogoImages($record);
        $bannerImages = $this->getBannerImages($record);
        $roomImages = $this->getRoomImages($record);
        $promotionImages = $this->getPromotionImages($record);
        
        $hasImages = false;
        
        // Logo Images
        if (!empty($logoImages)) {
            $hasImages = true;
            $components[] = Components\View::make('filament.admin.components.image-download-section')
                ->viewData([
                    'category' => 'Logo Images',
                    'images' => $logoImages,
                    'count' => count($logoImages),
                    'downloadAction' => 'downloadLogoImagesAsZip'
                ]);
        }
        
        // Banner Images
        if (!empty($bannerImages)) {
            $hasImages = true;
            $components[] = Components\View::make('filament.admin.components.image-download-section')
                ->viewData([
                    'category' => 'Banner Images',
                    'images' => $bannerImages,
                    'count' => count($bannerImages),
                    'downloadAction' => 'downloadBannerImagesAsZip'
                ]);
        }
        
        // Room Images
        if (!empty($roomImages)) {
            $hasImages = true;
            $components[] = Components\View::make('filament.admin.components.room-images-section')
                ->viewData([
                    'category' => 'Room Images',
                    'roomGroups' => $roomImages,
                    'downloadAction' => 'downloadRoomImagesAsZip'
                ]);
        }
        
        // Promotion Images
        if (!empty($promotionImages)) {
            $hasImages = true;
            $components[] = Components\View::make('filament.admin.components.promotion-images-section')
                ->viewData([
                    'category' => 'Promotion Images',
                    'promotionGroups' => $promotionImages,
                    'downloadAction' => 'downloadPromotionImagesAsZip'
                ]);
        }
        
        if (!$hasImages) {
            $components[] = Components\TextEntry::make('no_images')
                ->label('')
                ->default('No images found - This project does not have any uploaded images yet.')
                ->columnSpanFull();
        }
        
        return $components;
    }
    
    protected function getLogoImages($record): array
    {
        $images = [];
        
        // Get logo images from marketing team
        $logoData = ProjectData::where('project_id', $record->id)
            ->where('team', 'marketing')
            ->where('section', 'logos')
            ->get();
            
        foreach ($logoData as $data) {
            if (in_array($data->field_key, ['main_logo', 'email_logo', 'favicon']) && $data->field_value) {
                if (Storage::disk('public')->exists($data->field_value)) {
                    $images[] = [
                        'path' => $data->field_value,
                        'name' => $data->field_key . '.' . pathinfo($data->field_value, PATHINFO_EXTENSION),
                        'display_name' => ucwords(str_replace('_', ' ', $data->field_key)),
                        'url' => Storage::url($data->field_value)
                    ];
                }
            }
        }
        
        return $images;
    }
    
    protected function getBannerImages($record): array
    {
        $images = [];
        
        // Get all banner images from marketing team
        $bannerData = ProjectData::where('project_id', $record->id)
            ->where('team', 'marketing')
            ->where('section', 'banner_pictures')
            ->get();
            
        foreach ($bannerData as $data) {
            // Skip non-banner fields
            if ($data->field_key === 'use_same_banner') {
                continue;
            }
            
            // Check if it's a banner field and has a value
            if (str_ends_with($data->field_key, '_banner') && $data->field_value) {
                if (Storage::disk('public')->exists($data->field_value)) {
                    // Extract module name from field key (e.g., 'confirmation_banner' -> 'confirmation')
                    $moduleName = str_replace('_banner', '', $data->field_key);
                    
                    // Special handling for default banner
                    if ($moduleName === 'default') {
                        $displayName = 'Default Banner';
                    } else {
                        // Try to get the actual module name
                        $module = \App\Models\Module::where('slug', $moduleName)->first();
                        $displayName = $module ? $module->name : ucfirst(str_replace('_', ' ', $moduleName));
                    }
                    
                    $extension = pathinfo($data->field_value, PATHINFO_EXTENSION);
                    $images[] = [
                        'path' => $data->field_value,
                        'name' => $moduleName . '_banner.' . $extension,
                        'display_name' => $displayName,
                        'url' => Storage::url($data->field_value)
                    ];
                }
            }
        }
        
        return $images;
    }
    
    protected function getRoomImages($record): array
    {
        $groupedImages = [];
        
        // Get room types to have proper names
        $roomTypesData = ProjectData::where('project_id', $record->id)
            ->where('team', 'reservation')
            ->where('section', 'room_types')
            ->where('field_key', 'room_types')
            ->first();
            
        $roomTypes = [];
        if ($roomTypesData && $roomTypesData->field_value) {
            $roomTypesArray = json_decode($roomTypesData->field_value, true);
            if (is_array($roomTypesArray)) {
                foreach ($roomTypesArray as $index => $room) {
                    $roomTypes[$index] = [
                        'code' => $room['code'] ?? 'Room_' . $index,
                        'name' => $room['name'] ?? 'Room Type ' . ($index + 1)
                    ];
                }
            }
        }
        
        // Get all room detail fields (new structure)
        $roomDetailFields = ProjectData::where('project_id', $record->id)
            ->where('team', 'marketing')
            ->where('section', 'room_details')
            ->where(function($query) {
                $query->where('field_key', 'like', 'room_%_main_image')
                      ->orWhere('field_key', 'like', 'room_%_additional_image_%');
            })
            ->get();
        
        foreach ($roomDetailFields as $field) {
            if ($field->field_value && Storage::disk('public')->exists($field->field_value)) {
                // Extract room index from field key
                if (preg_match('/room_(\d+)_(.+)/', $field->field_key, $matches)) {
                    $roomIndex = (int) $matches[1];
                    $imageType = $matches[2];
                    
                    // Get room info
                    $roomCode = $roomTypes[$roomIndex]['code'] ?? 'ROOM' . ($roomIndex + 1);
                    $roomName = $roomTypes[$roomIndex]['name'] ?? 'Room ' . ($roomIndex + 1);
                    
                    // Initialize room group if not exists
                    if (!isset($groupedImages[$roomCode])) {
                        $groupedImages[$roomCode] = [
                            'room_code' => $roomCode,
                            'room_name' => $roomName,
                            'main_image' => null,
                            'slideshow_images' => []
                        ];
                    }
                    
                    // Determine display name and file name
                    if ($imageType === 'main_image') {
                        $displayName = 'Main Image';
                        $fileName = $roomCode . '_main.jpg';
                        $groupedImages[$roomCode]['main_image'] = [
                            'path' => $field->field_value,
                            'name' => $fileName,
                            'display_name' => $displayName,
                            'url' => Storage::url($field->field_value)
                        ];
                    } elseif (preg_match('/additional_image_(\d+)/', $imageType, $slideMatches)) {
                        $slideNumber = (int) $slideMatches[1];
                        $displayName = 'Slideshow Image ' . $slideNumber;
                        $fileName = $roomCode . '_slideshow_' . $slideNumber . '.jpg';
                        $groupedImages[$roomCode]['slideshow_images'][$slideNumber] = [
                            'path' => $field->field_value,
                            'name' => $fileName,
                            'display_name' => $displayName,
                            'url' => Storage::url($field->field_value)
                        ];
                    }
                }
            }
        }
        
        // Sort slideshow images by number
        foreach ($groupedImages as &$roomGroup) {
            ksort($roomGroup['slideshow_images']);
        }
        
        // Sort rooms by code
        ksort($groupedImages);
        
        return $groupedImages;
    }
    
    protected function getPromotionImages($record): array
    {
        $groupedPromotions = [];
        
        // Get promotion images from marketing team
        // Try different field keys as they can vary by brand
        $promotionData = ProjectData::where('project_id', $record->id)
            ->where('team', 'marketing')
            ->where('section', 'promotions')
            ->whereIn('field_key', ['promotions', 'promotion_items', 'promotion_tiles_items', 'concierge_perfect_day_items'])
            ->get();
            
        // Debug: Log what we found
        \Log::info('Promotion data found:', [
            'count' => $promotionData->count(),
            'field_keys' => $promotionData->pluck('field_key')->toArray()
        ]);
            
        foreach ($promotionData as $data) {
            if ($data->field_value) {
                $promotions = json_decode($data->field_value, true);
                if (is_array($promotions)) {
                    // Determine promotion type based on field key
                    $promotionType = str_replace('_items', '', $data->field_key);
                    $promotionTypeLabel = ucwords(str_replace('_', ' ', $promotionType));
                    
                    foreach ($promotions as $index => $promotion) {
                        if (isset($promotion['image']) && $promotion['image'] && Storage::disk('public')->exists($promotion['image'])) {
                            // Get name based on different field structures
                            $name = '';
                            if (isset($promotion['name'])) {
                                $name = $promotion['name'];
                            } elseif (isset($promotion['title'])) {
                                $name = $promotion['title'];
                            } else {
                                $name = $promotionTypeLabel . ' ' . ($index + 1);
                            }
                            
                            $priority = isset($promotion['priority']) ? $promotion['priority'] : '0';
                            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
                            
                            $groupedPromotions[] = [
                                'name' => $name,
                                'priority' => $priority,
                                'safe_name' => $safeName,
                                'promotion_type' => $promotionTypeLabel,
                                'image' => [
                                    'path' => $promotion['image'],
                                    'name' => $safeName . '_priority_' . $priority . '.jpg',
                                    'display_name' => $promotionTypeLabel . ' Image',
                                    'url' => Storage::url($promotion['image'])
                                ]
                            ];
                        }
                    }
                }
            }
        }
        
        // Sort by priority (descending) then by name
        usort($groupedPromotions, function($a, $b) {
            $priorityCompare = $b['priority'] - $a['priority'];
            if ($priorityCompare === 0) {
                return strcmp($a['name'], $b['name']);
            }
            return $priorityCompare;
        });
        
        return $groupedPromotions;
    }
    
    protected function getModuleName($moduleId): string
    {
        if (!$moduleId) {
            return 'default';
        }
        
        $module = \App\Models\Module::find($moduleId);
        return $module ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $module->name) : 'module_' . $moduleId;
    }
    
    public function downloadLogoImagesAsZip()
    {
        $images = $this->getLogoImages($this->record);
        return $this->createZipDownload($images, 'logo-images');
    }
    
    public function downloadBannerImagesAsZip()
    {
        $images = $this->getBannerImages($this->record);
        return $this->createZipDownload($images, 'banner-images');
    }
    
    public function downloadRoomImagesAsZip()
    {
        $roomGroups = $this->getRoomImages($this->record);
        
        if (empty($roomGroups)) {
            $this->dispatch('notify', [
                'message' => 'No room images to download',
                'type' => 'warning'
            ]);
            return;
        }
        
        $zipFileName = 'room-images-' . $this->record->access_code . '-' . date('Y-m-d-His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);
        
        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            foreach ($roomGroups as $roomGroup) {
                $roomCode = $roomGroup['room_code'];
                $folderName = $roomCode . '/';
                
                // Add main image to room folder
                if ($roomGroup['main_image']) {
                    $mainImage = $roomGroup['main_image'];
                    $filePath = Storage::disk('public')->path($mainImage['path']);
                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, $folderName . $mainImage['name']);
                    }
                }
                
                // Add slideshow images to room folder
                foreach ($roomGroup['slideshow_images'] as $slideshowImage) {
                    $filePath = Storage::disk('public')->path($slideshowImage['path']);
                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, $folderName . $slideshowImage['name']);
                    }
                }
            }
            $zip->close();
            
            return response()->download($zipPath)->deleteFileAfterSend();
        }
        
        $this->dispatch('notify', [
            'message' => 'Failed to create ZIP file',
            'type' => 'danger'
        ]);
    }
    
    public function downloadPromotionImagesAsZip()
    {
        $promotionGroups = $this->getPromotionImages($this->record);
        
        if (empty($promotionGroups)) {
            $this->dispatch('notify', [
                'message' => 'No promotion images to download',
                'type' => 'warning'
            ]);
            return;
        }
        
        $zipFileName = 'promotion-images-' . $this->record->access_code . '-' . date('Y-m-d-His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);
        
        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            foreach ($promotionGroups as $promotionGroup) {
                $folderName = $promotionGroup['safe_name'] . '/';
                
                // Add promotion image to folder
                if ($promotionGroup['image']) {
                    $filePath = Storage::disk('public')->path($promotionGroup['image']['path']);
                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, $folderName . $promotionGroup['image']['name']);
                    }
                }
            }
            $zip->close();
            
            return response()->download($zipPath)->deleteFileAfterSend();
        }
        
        $this->dispatch('notify', [
            'message' => 'Failed to create ZIP file',
            'type' => 'danger'
        ]);
    }
    
    protected function formatSectionName($section): string
    {
        return ucwords(str_replace('_', ' ', $section));
    }
    
    protected function createZipDownload($images, $prefix)
    {
        if (empty($images)) {
            $this->dispatch('notify', [
                'message' => 'No images to download',
                'type' => 'warning'
            ]);
            return;
        }
        
        $zipFileName = $prefix . '-' . $this->record->access_code . '-' . date('Y-m-d-His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);
        
        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            foreach ($images as $image) {
                $filePath = Storage::disk('public')->path($image['path']);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $image['name']);
                }
            }
            $zip->close();
            
            return response()->download($zipPath)->deleteFileAfterSend();
        }
        
        $this->dispatch('notify', [
            'message' => 'Failed to create ZIP file',
            'type' => 'danger'
        ]);
    }
    
}