<?php

namespace Threls\FilamentPageBuilder\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Threls\FilamentPageBuilder\Models\MenuItem;

#[MapName(SnakeCaseMapper::class)]
class MenuItemData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $type,
        public ?string $url,
        public ?string $path,
        public string $target,
        public ?string $icon,
        public ?string $iconAlt,
        public bool $isVisible,
        public int $order,
        public ?string $slug,
        /** @var MenuItemData[] */
        public ?Collection $children = null,
    ) {}

    public static function fromModel(MenuItem $menuItem): self
    {
        return new self(
            id: $menuItem->id,
            name: $menuItem->name ?? 'Untitled',
            type: $menuItem->type,
            url: $menuItem->getUrl(),
            path: $menuItem->getHierarchicalPath(),
            target: $menuItem->target,
            icon: $menuItem->icon,
            iconAlt: $menuItem->icon_alt,
            isVisible: $menuItem->is_visible,
            order: $menuItem->order,
            slug: $menuItem->getSlug(),
            children: $menuItem->children->count() > 0
                ? self::collect($menuItem->children)
                : null,
        );
    }
}
