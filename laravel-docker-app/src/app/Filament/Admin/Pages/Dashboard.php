<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\ProjectCalendarWidget::class,
            \App\Filament\Admin\Widgets\AdminStatsOverview::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }
}