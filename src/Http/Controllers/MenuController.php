<?php

namespace Threls\FilamentPageBuilder\Http\Controllers;

use Illuminate\Routing\Controller;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Threls\FilamentPageBuilder\Data\MenuData;
use Threls\FilamentPageBuilder\Models\Menu;

class MenuController extends Controller
{
    public function __invoke()
    {
        $menus = $this->getActiveMenusWithItems();

        return [
            'data' => MenuData::collect($menus),
            'locale' => app()->getLocale(),
        ];
    }

    private function getActiveMenusWithItems()
    {
        return QueryBuilder::for(Menu::class)
            ->allowedFilters([
                AllowedFilter::exact('location'),
            ])
            ->where('status', 'active')
            ->with(['menuItems' => $this->menuItemsQuery()])
            ->get();
    }

    private function menuItemsQuery(): \Closure
    {
        return function ($query) {
            $recursiveChildren = $this->recursiveChildrenQuery();

            $query->whereNull('parent_id')
                ->where('is_visible', true)
                ->orderBy('order')
                ->with(['children' => $recursiveChildren]);
        };
    }

    private function recursiveChildrenQuery(): \Closure
    {
        $recursiveChildren = function ($childQuery) use (&$recursiveChildren) {
            $childQuery->where('is_visible', true)
                ->orderBy('order')
                ->with(['children' => $recursiveChildren]);
        };

        return $recursiveChildren;
    }
}