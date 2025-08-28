<?php

namespace Threls\FilamentPageBuilder\Resources\CompositionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Threls\FilamentPageBuilder\Resources\CompositionResource;

class ListCompositions extends ListRecords
{
    protected static string $resource = CompositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
