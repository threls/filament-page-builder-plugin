<?php

namespace Threls\FilamentPageBuilder\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class MenuItem extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'type',
        'icon',
        'icon_alt',
        'target',
        'order',
        'is_visible',
        'page_id',
    ];

    public array $translatedAttributes = ['name', 'url'];

    protected $casts = [
        'is_visible' => 'boolean',
        'order' => 'integer',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->orderBy('order')
            ->with('children');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function getDepth(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    public function canHaveChildren(): bool
    {
        $currentDepth = $this->getDepth();
        $maxDepth = $this->menu->max_depth ?? 3;

        return $currentDepth < ($maxDepth - 1);
    }

    public function getUrl(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        $translationUrl = $this->translations->first()?->url ?? $this->translate($locale)?->url;
        if (!empty($translationUrl)) {
            return $translationUrl;
        }

        if ($this->type === 'page' && $this->page) {
            return $this->buildHierarchicalPagePath($locale);
        }

        return null;
    }

    public function buildHierarchicalPagePath(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if (!$this->page) {
            return null;
        }

        $pageSlug = $this->page->slug ?? '';
        if (!$pageSlug) {
            return null;
        }

        $pathSegments = [];
        $currentItem = $this->parent;

        while ($currentItem) {
            $parentTranslation = $currentItem->translate($locale);
            if ($parentTranslation && $parentTranslation->name) {
                $parentSlug = $this->nameToSlug($parentTranslation->name);
                array_unshift($pathSegments, $parentSlug);
            }
            $currentItem = $currentItem->parent;
        }

        $pathSegments[] = $pageSlug;

        return '/' . implode('/', $pathSegments);
    }

    private function nameToSlug(string $name): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    }

    public function isCmsPage(): bool
    {
        return $this->type === 'page' && $this->page_id !== null;
    }

    public function getPageSlug(): ?string
    {
        if (!$this->isCmsPage() || !$this->page) {
            return null;
        }

        return $this->page->slug ?? null;
    }

    public function getSlug(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($this->isCmsPage() && $this->page) {
            return $this->page->slug ?? null;
        }

        $translation = $this->translate($locale);
        if ($translation && $translation->name) {
            return $this->nameToSlug($translation->name);
        }

        return null;
    }

    public function getHierarchicalPath(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($this->isCmsPage() && $this->page) {
            return $this->buildHierarchicalPagePath($locale);
        }

        $pathSegments = [];
        $currentItem = $this;

        while ($currentItem) {
            $translation = $currentItem->translate($locale);
            if ($translation && $translation->name) {
                $slug = $this->nameToSlug($translation->name);
                array_unshift($pathSegments, $slug);
            }
            $currentItem = $currentItem->parent;
        }

        return empty($pathSegments) ? null : '/' . implode('/', $pathSegments);
    }



    public function getNameAttribute(): ?string
    {
        return $this->translate()?->name;
    }

    protected static function booted(): void
    {
        static::deleting(function (MenuItem $menuItem) {
            $menuItem->children()->delete();
        });
    }
}
