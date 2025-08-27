<?php

namespace Threls\FilamentPageBuilder\Resources\MenuResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Threls\FilamentPageBuilder\Resources\MenuResource;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}