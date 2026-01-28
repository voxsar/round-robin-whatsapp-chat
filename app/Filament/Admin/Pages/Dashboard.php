<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\AgentPerformanceMatrixWidget;
use App\Filament\Admin\Widgets\PerformanceStatsWidget;
use App\Filament\Admin\Widgets\StagePipelineStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected function getHeaderWidgets(): array
    {
        return [
            PerformanceStatsWidget::class,
            StagePipelineStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            AgentPerformanceMatrixWidget::class,
        ];
    }
}
