<?php

namespace Threls\FilamentPageBuilder\Resources\PageLayoutResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Threls\FilamentPageBuilder\Resources\PageLayoutResource;

class ListPageLayouts extends ListRecords
{
    protected static string $resource = PageLayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
