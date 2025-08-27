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
        $menus = QueryBuilder::for(Menu::class)
            ->allowedFilters([
                AllowedFilter::exact('location'),
            ])
            ->where('status', 'active')
            ->with(['menuItems' => function ($query) {
                $query->whereNull('parent_id')
                    ->where('is_visible', true)
                    ->orderBy('order')
                    ->with(['children' => function ($childQuery) {
                        $childQuery->where('is_visible', true)
                            ->orderBy('order');
                    }]);
            }])
            ->get();

        return [
            'data' => MenuData::collect($menus),
            'locale' => app()->getLocale(),
        ];
    }
}