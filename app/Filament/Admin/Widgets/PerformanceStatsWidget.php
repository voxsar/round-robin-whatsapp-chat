<?php

namespace App\Filament\Admin\Widgets;

use App\Models\ChatSession;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PerformanceStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $query = ChatSession::query();

        if ($user?->role === 'manager') {
            $agentIds = $user->directReports()->pluck('id')->push($user->id);
            $query->whereIn('assigned_user_id', $agentIds);
        } elseif ($user?->role === 'agent') {
            $query->where('assigned_user_id', $user->id);
        }

        $totalChats = (clone $query)->count();
        $activeChats = (clone $query)->where('status', 'active')->count();
        $resolvedChats = (clone $query)->where('status', 'resolved')->count();

        $avgResponseSeconds = (clone $query)
            ->whereNotNull('first_response_at')
            ->selectRaw('AVG(' . $this->responseTimeExpression() . ') as avg_response_seconds')
            ->value('avg_response_seconds');

        $avgResponseMinutes = $avgResponseSeconds ? round($avgResponseSeconds / 60, 2) : 0;

        return [
            Stat::make('Total Chats', $totalChats),
            Stat::make('Active Chats', $activeChats),
            Stat::make('Resolved Chats', $resolvedChats),
            Stat::make('Avg First Response (min)', $avgResponseMinutes),
        ];
    }

    private function responseTimeExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'pgsql' => "EXTRACT(EPOCH FROM (first_response_at - created_at))",
            'sqlite' => "((julianday(first_response_at) - julianday(created_at)) * 86400)",
            default => "TIMESTAMPDIFF(SECOND, created_at, first_response_at)",
        };
    }
}
