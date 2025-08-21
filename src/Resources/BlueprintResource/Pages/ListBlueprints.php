<?php

namespace Threls\FilamentPageBuilder\Resources\BlueprintResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Threls\FilamentPageBuilder\Resources\BlueprintResource;

class ListBlueprints extends ListRecords
{
    protected static string $resource = BlueprintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
