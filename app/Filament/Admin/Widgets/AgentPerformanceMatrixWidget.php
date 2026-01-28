<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AgentPerformanceMatrixWidget extends TableWidget
{
    protected static ?string $heading = 'Agent Performance Matrix';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getAgentQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Agent')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('total_chats')
                    ->label('Total Chats')
                    ->sortable(),
                TextColumn::make('active_chats')
                    ->label('Active')
                    ->sortable(),
                TextColumn::make('resolved_chats')
                    ->label('Resolved')
                    ->sortable(),
                TextColumn::make('avg_response_minutes')
                    ->label('Avg First Response (min)')
                    ->sortable(),
            ]);
    }

    private function getAgentQuery(): Builder
    {
        $user = auth()->user();
        $driver = DB::connection()->getDriverName();

        $responseExpression = match ($driver) {
            'pgsql' => "EXTRACT(EPOCH FROM (chat_sessions.first_response_at - chat_sessions.created_at))",
            'sqlite' => "((julianday(chat_sessions.first_response_at) - julianday(chat_sessions.created_at)) * 86400)",
            default => "TIMESTAMPDIFF(SECOND, chat_sessions.created_at, chat_sessions.first_response_at)",
        };

        $query = User::query()
            ->where('role', 'agent')
            ->leftJoin('chat_sessions', 'chat_sessions.assigned_user_id', '=', 'users.id')
            ->select('users.id', 'users.name')
            ->selectRaw('COUNT(chat_sessions.id) as total_chats')
            ->selectRaw('SUM(CASE WHEN chat_sessions.status = "active" THEN 1 ELSE 0 END) as active_chats')
            ->selectRaw('SUM(CASE WHEN chat_sessions.status = "resolved" THEN 1 ELSE 0 END) as resolved_chats')
            ->selectRaw("ROUND(AVG({$responseExpression}) / 60, 2) as avg_response_minutes")
            ->groupBy('users.id', 'users.name');

        if ($user?->role === 'manager') {
            $agentIds = $user->directReports()->pluck('id')->push($user->id);
            $query->whereIn('users.id', $agentIds);
        } elseif ($user?->role === 'agent') {
            $query->where('users.id', $user->id);
        }

        return $query;
    }
}
