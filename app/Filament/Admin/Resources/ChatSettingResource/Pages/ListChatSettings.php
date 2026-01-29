<?php

namespace App\Filament\Admin\Resources\ChatSettingResource\Pages;

use App\Filament\Admin\Resources\ChatSettingResource;
use App\Models\ChatSetting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChatSettings extends ListRecords
{
    protected static string $resource = ChatSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function mount(): void
    {
        parent::mount();

        if (! ChatSetting::query()->exists()) {
            ChatSetting::query()->create();
        }
    }
}
