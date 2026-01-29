<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ChatSessionResource\Pages;
use App\Models\ChatSession;
use App\Models\User;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use App\Models\ChatMessage;
use App\Services\PusherClient;
use App\Services\WhatsappClient;
use Illuminate\Database\Eloquent\Builder;

class ChatSessionResource extends Resource
{
    protected static ?string $model = ChatSession::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Operations';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Chat Sessions';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('mobile')
                    ->maxLength(30),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'resolved' => 'Resolved',
                        'archived' => 'Archived',
                        'blocked' => 'Blocked',
                        'ended' => 'Ended',
                    ])
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
                TextColumn::make('person.name')
                    ->label('Person')
                    ->sortable(),
                TextColumn::make('assignedUser.name')
                    ->label('Assigned Agent')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('first_response_at')
                    ->dateTime()
                    ->label('First Response'),
                TextColumn::make('last_response_at')
                    ->dateTime()
                    ->label('Last Response'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'resolved' => 'Resolved',
                        'archived' => 'Archived',
                        'blocked' => 'Blocked',
                        'ended' => 'Ended',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('sendMessage')
                    ->label('Reply')
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        Textarea::make('message')
                            ->required()
                            ->maxLength(2000)
                            ->rows(3),
                    ])
                    ->action(function (ChatSession $record, array $data): void {
                        if ($record->status === 'blocked') {
                            return;
                        }

                        if (! $record->instance || ! $record->group_jid) {
                            return;
                        }

                        $record->restoreFromEnded();

                        app(WhatsappClient::class)->sendText($record->instance, [
                            'number' => $record->group_jid,
                            'text' => $data['message'],
                        ]);

                        $message = ChatMessage::create([
                            'chat_session_id' => $record->id,
                            'user_id' => auth()->id(),
                            'sender' => 'agent',
                            'sender_name' => auth()->user()?->name,
                            'text' => $data['message'],
                            'source' => 'filament',
                            'sent_at' => now(),
                        ]);

                        if (! $record->first_response_at) {
                            $record->first_response_at = now();
                        }
                        $record->last_response_at = now();

                        $channelKey = $record->group_id ?: $record->group_jid;
                        $channel = $channelKey ? "group-{$channelKey}" : $record->pusher_channel;
                        if ($channel) {
                            app(PusherClient::class)->trigger($channel, 'message', [
                                'message' => [
                                    'id' => (string) $message->id,
                                    'sender' => 'agent',
                                    'sender_name' => auth()->user()?->name,
                                    'text' => $data['message'],
                                    'timestamp' => now()->toIso8601String(),
                                ],
                            ]);
                        }

                        if ($channel && $record->pusher_channel !== $channel) {
                            $record->pusher_channel = $channel;
                        }
                        $record->save();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListChatSessions::route('/'),
            'create' => Pages\CreateChatSession::route('/create'),
            'edit' => Pages\EditChatSession::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user();
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
