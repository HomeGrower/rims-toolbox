<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'active')->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $averageProgress = Project::where('status', 'active')
            ->get()
            ->avg('overall_progress') ?? 0;

        return [
            Stat::make('Total Projects', $totalProjects)
                ->description('All time')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary'),
                
            Stat::make('Active Projects', $activeProjects)
                ->description('Currently running')
                ->descriptionIcon('heroicon-m-play')
                ->color('success'),
                
            Stat::make('Completed Projects', $completedProjects)
                ->description('Successfully finished')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),
                
            Stat::make('Average Progress', round($averageProgress) . '%')
                ->description('Active projects')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
        ];
    }
}