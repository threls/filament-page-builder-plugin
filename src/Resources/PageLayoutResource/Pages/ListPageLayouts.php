<?php

namespace Threls\FilamentPageBuilder\Resources\PageLayoutResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Threls\FilamentPageBuilder\Resources\PageLayoutResource;

class ListPageLayouts extends ListRecords
{
    protected static string $resource = PageLayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
