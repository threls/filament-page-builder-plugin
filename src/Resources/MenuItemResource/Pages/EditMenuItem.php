<?php

namespace Threls\FilamentPageBuilder\Resources\MenuItemResource\Pages;

use CactusGalaxy\FilamentAstrotomic\Resources\Pages\Record\EditTranslatable;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Threls\FilamentPageBuilder\Resources\MenuItemResource;

class EditMenuItem extends EditRecord
{
    use EditTranslatable;

    protected static string $resource = MenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        $menuId = $this->record->menu_id;
        if ($menuId) {
            return route('filament.admin.resources.menus.edit', $menuId);
        }

        return $this->getResource()::getUrl('index');
    }
}
