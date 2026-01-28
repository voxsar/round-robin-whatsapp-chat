<?php

namespace App\Filament\Admin\Pages;

use App\Models\Person;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;

class PeopleKanbanBoard extends BoardPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';
    protected static string|\UnitEnum|null $navigationGroup = 'Operations';
    protected static ?string $title = 'People Kanban';
    protected static ?string $navigationLabel = 'People Kanban';

    public function board(Board $board): Board
    {
        return $board
            ->query($this->getEloquentQuery())
            ->recordTitleAttribute('name')
            ->columnIdentifier('stage')
            ->positionIdentifier('position') // Enable drag-and-drop with position field
            ->columns([
                Column::make('new')->label('New')->color('gray'),
                Column::make('qualified')->label('Qualified')->color('info'),
                Column::make('in_progress')->label('In Progress')->color('warning'),
                Column::make('resolved')->label('Resolved')->color('success'),
                Column::make('archived')->label('Archived')->color('secondary'),
            ]);
    }

    public function getEloquentQuery(): Builder
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
