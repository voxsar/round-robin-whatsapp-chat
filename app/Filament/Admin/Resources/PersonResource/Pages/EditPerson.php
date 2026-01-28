<?php

namespace App\Filament\Admin\Resources\PersonResource\Pages;

use App\Filament\Admin\Resources\PersonResource;
use Filament\Resources\Pages\EditRecord;

class EditPerson extends EditRecord
{
    protected static string $resource = PersonResource::class;
}
