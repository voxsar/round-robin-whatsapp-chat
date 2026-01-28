<?php

namespace App\Filament\Admin\Pages;

use App\Models\Person;
use Illuminate\Database\Eloquent\Builder;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

class PeopleKanbanBoard extends KanbanBoard
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $title = 'People Kanban';
    protected static ?string $navigationLabel = 'People Kanban';
    protected static string $model = Person::class;
    protected static string $recordStatusAttribute = 'stage';
    protected static string $recordTitleAttribute = 'name';

    protected function statuses(): array
    {
        return [
            'new' => [
                'title' => 'New',
                'color' => 'gray',
            ],
            'qualified' => [
                'title' => 'Qualified',
                'color' => 'info',
            ],
            'in_progress' => [
                'title' => 'In Progress',
                'color' => 'warning',
            ],
            'resolved' => [
                'title' => 'Resolved',
                'color' => 'success',
            ],
            'archived' => [
                'title' => 'Archived',
                'color' => 'secondary',
            ],
        ];
    }

    protected function getEloquentQuery(): Builder
    {
        $query = Person::query()->with('assignedUser');
        $user = auth()->user();

        if ($user?->role === 'manager') {
            $agentIds = $user->directReports()->pluck('id')->push($user->id);
            $query->whereIn('assigned_user_id', $agentIds);
        } elseif ($user?->role === 'agent') {
            $query->where('assigned_user_id', $user->id);
        }

        return $query;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user();
    }
}
