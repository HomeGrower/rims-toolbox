<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $userId = auth()->id();
        
        // Count created projects
        $createdProjects = Project::where('created_by', $userId)->count();
        
        // Count delegated projects
        $delegatedProjects = Project::where('delegated_to', $userId)->count();
        
        // Count all projects (created + delegated)
        $totalProjects = Project::where(function ($q) use ($userId) {
            $q->where('created_by', $userId)
              ->orWhere('delegated_to', $userId);
        })->count();
        
        // Count active projects (created + delegated)
        $activeProjects = Project::where(function ($q) use ($userId) {
            $q->where('created_by', $userId)
              ->orWhere('delegated_to', $userId);
        })->where('status', 'active')->count();

        return [
            Stat::make('Total Projects', $totalProjects)
                ->description($createdProjects . ' created, ' . $delegatedProjects . ' delegated')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary'),
            
            Stat::make('Active Projects', $activeProjects)
                ->description('Currently in progress')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            
            Stat::make('Delegated to Me', $delegatedProjects)
                ->description('Projects assigned by others')
                ->descriptionIcon('heroicon-m-arrow-right-circle')
                ->color('info'),
        ];
    }
}