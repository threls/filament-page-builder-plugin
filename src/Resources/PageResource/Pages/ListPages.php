<?php

namespace Threls\FilamentPageBuilder\Resources\PageResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Threls\FilamentPageBuilder\Resources\PageResource;

class ListPages extends ListRecords
{

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
