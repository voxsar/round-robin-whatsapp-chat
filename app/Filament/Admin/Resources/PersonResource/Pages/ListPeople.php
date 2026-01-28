<?php

namespace App\Filament\Admin\Resources\PersonResource\Pages;

use App\Filament\Admin\Resources\PersonResource;
use Filament\Resources\Pages\ListRecords;

class ListPeople extends ListRecords
{
    protected static string $resource = PersonResource::class;
}
