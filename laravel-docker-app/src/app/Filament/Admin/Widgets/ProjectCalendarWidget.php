<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Project;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Saade\FilamentFullCalendar\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Illuminate\Database\Eloquent\Model;

class ProjectCalendarWidget extends FullCalendarWidget
{
    protected static ?int $sort = -2;
    
    public Model | string | null $model = Project::class;

    protected function headerActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->hidden(), // Hide create action for now
        ];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        return Project::query()
            ->with(['hotelChain', 'hotelBrand'])
            ->where('created_at', '>=', $fetchInfo['start'])
            ->where('created_at', '<=', $fetchInfo['end'])
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'title' => $project->name,
                'start' => $project->created_at,
                'end' => $project->created_at,
                'backgroundColor' => $project->is_demo ? '#9CA3AF' : '#F7A600',
                'borderColor' => $project->is_demo ? '#6B7280' : '#F7A600',
                'textColor' => $project->is_demo ? '#1F2937' : '#FFFFFF',
                'extendedProps' => [
                    'hotel_chain' => $project->hotelChain?->name ?? 'N/A',
                    'hotel_brand' => $project->hotelBrand?->name ?? 'N/A',
                    'access_code' => $project->access_code,
                    'status' => $project->is_demo ? 'Demo' : 'Active',
                ],
            ])
            ->toArray();
    }

    public function eventDidMount(): string
    {
        return <<<JS
            function({ event, el }) {
                el.setAttribute('title', event.title + ' - ' + event.extendedProps.hotel_chain + '/' + event.extendedProps.hotel_brand + ' (' + event.extendedProps.access_code + ')');
            }
        JS;
    }

    public function config(): array
    {
        return [
            'firstDay' => 1, // Monday
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,dayGridWeek',
            ],
            'height' => 'auto',
            'contentHeight' => 600,
            'initialView' => 'dayGridMonth',
            'slotMinTime' => '00:00:00',
            'slotMaxTime' => '23:59:59',
            'eventTimeFormat' => [
                'hour' => '2-digit',
                'minute' => '2-digit',
                'meridiem' => false,
            ],
        ];
    }
}