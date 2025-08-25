<?php

namespace Threls\FilamentPageBuilder\Resources\PageResource\Support;

use Illuminate\Support\Collection;
use Threls\FilamentPageBuilder\Models\BlueprintVersion;
use Threls\FilamentPageBuilder\Models\PageLayout;

class PageBuilderUtils
{
    public static function getAllLayoutsWithColumns(): ?Collection
    {
        static $cache = null;
        if ($cache === null) {
            $cache = PageLayout::with('columns')->get();
        }
        return $cache;
    }

    public static function getLayoutById(int $layoutId): ?PageLayout
    {
        static $map = null;
        if ($map === null) {
            $map = self::getAllLayoutsWithColumns()->keyBy('id');
        }
        return $map->get($layoutId);
    }

    public static function getPublishedBlueprintVersions(): ?Collection
    {
        static $cache = null;
        if ($cache === null) {
            $cache = BlueprintVersion::query()
                ->where('status', 'published')
                ->with('blueprint')
                ->orderBy('blueprint_id')
                ->orderBy('version')
                ->get();
        }
        return $cache;
    }

    public static function getBlueprintVersionById(int $id): ?BlueprintVersion
    {
        return self::getPublishedBlueprintVersions()->firstWhere('id', $id)
            ?? BlueprintVersion::query()->find($id);
    }

    // Blueprint UI schema helpers moved to PageBuilderFormatUtil.

    public static function normalizeCategoryKey(?string $category): string
    {
        $c = strtolower((string) ($category ?: 'uncategorized'));
        return preg_replace('/[^a-z0-9]/', '', $c) ?: 'uncategorized';
    }

    public static function humanizeCategory(?string $category): string
    {
        $c = trim((string) ($category ?: 'Uncategorized'));
        return ucwords(strtolower($c));
    }
}
