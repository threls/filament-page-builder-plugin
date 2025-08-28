<?php

namespace Threls\FilamentPageBuilder\Resources\RelationshipTypeResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Threls\FilamentPageBuilder\Resources\RelationshipTypeResource;

class ListRelationshipTypes extends ListRecords
{
    protected static string $resource = RelationshipTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
