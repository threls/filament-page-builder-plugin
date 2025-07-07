<?php

namespace Threls\FilamentPageBuilder\Resources\PageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;
use Threls\FilamentPageBuilder\Resources\PageResource;

class CreatePage extends CreateRecord
{
    use Translatable;
    protected static string $resource = PageResource::class;
}
