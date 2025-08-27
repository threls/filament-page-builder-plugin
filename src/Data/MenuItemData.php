<?php

namespace Threls\FilamentPageBuilder\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Threls\FilamentPageBuilder\Models\MenuItem;

class MenuItemData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $type,
        public ?string $url,
        public string $target,
        public ?string $icon,
        public bool $is_visible,
        public int $order,
        /** @var MenuItemData[] */
        public ?Collection $children = null,
    ) {}

    public static function fromModel(MenuItem $menuItem): self
    {
        return new self(
            id: $menuItem->id,
            name: $menuItem->name,
            type: $menuItem->type,
            url: $menuItem->getUrl(),
            target: $menuItem->target,
            icon: $menuItem->getIconUrl(),
            is_visible: $menuItem->is_visible,
            order: $menuItem->order,
            children: $menuItem->children->count() > 0
                ? self::collect($menuItem->children)
                : null,
        );
    }
}
