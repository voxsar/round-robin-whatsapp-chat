<?php

namespace App\Filament\Admin\Resources\PersonResource\Pages;

use App\Filament\Admin\Resources\PersonResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePerson extends CreateRecord
{
    protected static string $resource = PersonResource::class;
}
