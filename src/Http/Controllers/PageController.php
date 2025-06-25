<?php

namespace Threls\FilamentPageBuilder\Http\Controllers;

use Illuminate\Routing\Controller;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Threls\FilamentPageBuilder\Data\PageData;
use Threls\FilamentPageBuilder\Enums\PageStatusEnum;
use Threls\FilamentPageBuilder\Models\Page;

class PageController extends Controller
{
    public function __invoke()
    {
        $pages = QueryBuilder::for(Page::class)
            ->allowedFilters([
                AllowedFilter::exact('slug'),
            ])
            ->whereStatus(PageStatusEnum::PUBLISHED)
            ->whereDoesntHave('resource')
            ->with('media')
            ->get();

        return [
            'data' => PageData::collect($pages),
            'locale' => app()->getLocale(),
        ];
    }
}
