<?php

namespace Threls\FilamentPageBuilder\Resources\MenuItemResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Threls\FilamentPageBuilder\Models\MenuItem;
use Threls\FilamentPageBuilder\Resources\MenuItemResource;

class CreateMenuItem extends CreateRecord
{
    protected static string $resource = MenuItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! isset($data['order']) || empty($data['order'])) {
            $data['order'] = MenuItem::where('menu_id', $data['menu_id'])
                ->where('parent_id', $data['parent_id'] ?? null)
                ->max('order') + 1;
        }

        return $data;
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
