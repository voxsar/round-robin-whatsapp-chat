<?php

namespace App\Filament\Admin\Resources\ChatSettingResource\Pages;

use App\Filament\Admin\Resources\ChatSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditChatSetting extends EditRecord
{
    protected static string $resource = ChatSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
