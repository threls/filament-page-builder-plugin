<?php

namespace Threls\FilamentPageBuilder\Resources\PageResource\Support;

use Illuminate\Support\Collection;
use Threls\FilamentPageBuilder\Models\BlueprintVersion;
use Threls\FilamentPageBuilder\Models\Composition;
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

    public static function getActiveCompositions(): ?Collection
    {
        static $cache = null;
        if ($cache === null) {
            $cache = Composition::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }
        return $cache;
    }

    public static function getCompositionById(int $id): ?Composition
    {
        $list = self::getActiveCompositions();
        $found = $list?->firstWhere('id', $id);
        return $found ?: Composition::query()->find($id);
    }

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

    /**
     * Build the label for a blueprint version block for the editor palette.
     * If a category exists, prefixes with "Category · Name"; otherwise uses just the name.
     * Appends " (deprecated)" when the provided latest published version for the same blueprint
     * is greater than the current version number.
     */
    public static function formatBlueprintVersionLabel(BlueprintVersion $bv, ?int $latestPublishedVersionForBlueprint = null): string
    {
        $category = $bv->blueprint?->category ?? null;
        $categoryLabel = self::humanizeCategory($category);
        $name = $bv->blueprint?->name ?? 'Blueprint';
        $label = $category ? sprintf('%s · %s', $categoryLabel, $name) : $name;

        $currentVersion = (int) ($bv->version ?? 0);
        if ($latestPublishedVersionForBlueprint && $currentVersion < $latestPublishedVersionForBlueprint) {
            $label .= ' (deprecated)';
        }

        return $label;
    }
}
