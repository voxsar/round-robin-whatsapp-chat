<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PersonResource\Pages;
use App\Models\Person;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class PersonResource extends Resource
{
    protected static ?string $model = Person::class;
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'People';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('mobile')
                    ->maxLength(30),
                Select::make('stage')
                    ->options(self::stageOptions())
                    ->required(),
                Select::make('assigned_user_id')
                    ->label('Assigned Agent')
                    ->options(fn () => self::availableAgents())
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('mobile'),
                TextColumn::make('stage')
                    ->badge()
                    ->sortable(),
                TextColumn::make('assignedUser.name')
                    ->label('Assigned Agent')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('stage')
                    ->options(self::stageOptions()),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->role === 'manager') {
            $agentIds = $user->directReports()->pluck('id')->push($user->id);
            return $query->whereIn('assigned_user_id', $agentIds);
        }

        if ($user->role === 'agent') {
            return $query->where('assigned_user_id', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPeople::route('/'),
            'create' => Pages\CreatePerson::route('/create'),
            'edit' => Pages\EditPerson::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user();
    }

    public static function stageOptions(): array
    {
        return [
            'new' => 'New',
            'qualified' => 'Qualified',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'archived' => 'Archived',
        ];
    }

    public static function availableAgents(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $query = User::query()->where('role', 'agent');

        if ($user->role === 'manager') {
            $query->whereIn('id', $user->directReports()->pluck('id')->push($user->id));
        }

        if ($user->role === 'agent') {
            $query->where('id', $user->id);
        }

        return $query->orderBy('name')->pluck('name', 'id')->all();
    }
}
