<?php

namespace Threls\FilamentPageBuilder\Resources\PageResource\Pages;

use CactusGalaxy\FilamentAstrotomic\Resources\Pages\Record\CreateTranslatable;
use Filament\Resources\Pages\CreateRecord;
use Threls\FilamentPageBuilder\Resources\PageResource;

class CreatePage extends CreateRecord
{
    use CreateTranslatable;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
