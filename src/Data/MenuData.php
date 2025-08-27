<?php

    namespace Threls\FilamentPageBuilder\Data;

    use Illuminate\Support\Collection;
    use Spatie\LaravelData\Data;
    use Spatie\LaravelData\DataCollection;
    use Threls\FilamentPageBuilder\Models\Menu;

    class MenuData extends Data
    {
        public function __construct(
            public int $id,
            public string $name,
            public ?string $description,
            public string $location,
            public string $status,
            public int $max_depth,
            /** @var MenuItemData[] */
            public Collection $items,
        ) {
        }

        public static function fromModel(Menu $menu): self
        {
            return new self(
                id: $menu->id,
                name: $menu->name,
                description: $menu->description,
                location: $menu->location,
                status: $menu->status,
                max_depth: $menu->max_depth,
                items: MenuItemData::collect($menu->menuItems),
            );
        }
    }
