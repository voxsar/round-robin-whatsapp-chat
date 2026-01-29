<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ChatSettingResource\Pages;
use App\Models\ChatSetting;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ChatSettingResource extends Resource
{
    protected static ?string $model = ChatSetting::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Chat Settings';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('bot_number')
                    ->label('Bot WhatsApp Number')
                    ->placeholder('+1 555 000 0000')
                    ->maxLength(40),
                TextInput::make('away_after_minutes')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->label('Away After (minutes)'),
                TextInput::make('end_after_minutes')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->label('End After (minutes)'),
                Textarea::make('away_message')
                    ->rows(3)
                    ->label('Away Message'),
                Textarea::make('end_message')
                    ->rows(3)
                    ->label('End Message'),
                Textarea::make('user_end_message')
                    ->rows(2)
                    ->label('User Ended Message'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bot_number')->label('Bot Number'),
                TextColumn::make('away_after_minutes')->label('Away After'),
                TextColumn::make('end_after_minutes')->label('End After'),
                TextColumn::make('updated_at')->dateTime()->label('Updated'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatSettings::route('/'),
            'edit' => Pages\EditChatSetting::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
