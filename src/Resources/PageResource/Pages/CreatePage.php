<?php

namespace Threls\FilamentPageBuilder\Resources\PageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Threls\FilamentPageBuilder\Resources\PageResource;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;
}