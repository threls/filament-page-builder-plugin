<?php

namespace Threls\FilamentPageBuilder\Resources\PageResource\Pages;

use CactusGalaxy\FilamentAstrotomic\Resources\Pages\Record\EditTranslatable;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Threls\FilamentPageBuilder\Resources\PageResource;

class EditPage extends EditRecord
{
    use EditTranslatable;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
