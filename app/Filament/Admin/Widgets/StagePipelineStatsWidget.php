<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Person;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StagePipelineStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $query = Person::query();

        if ($user?->role === 'manager') {
            $agentIds = $user->directReports()->pluck('id')->push($user->id);
            $query->whereIn('assigned_user_id', $agentIds);
        } elseif ($user?->role === 'agent') {
            $query->where('assigned_user_id', $user->id);
        }

        return [
            Stat::make('New', (clone $query)->where('stage', 'new')->count()),
            Stat::make('Qualified', (clone $query)->where('stage', 'qualified')->count()),
            Stat::make('In Progress', (clone $query)->where('stage', 'in_progress')->count()),
            Stat::make('Resolved', (clone $query)->where('stage', 'resolved')->count()),
            Stat::make('Archived', (clone $query)->where('stage', 'archived')->count()),
        ];
    }
}
