<?php

namespace Threls\FilamentPageBuilder\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MenuItem extends Model implements HasMedia, TranslatableContract
{
    use InteractsWithMedia;
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

        switch ($this->type) {
            case 'page':
                if ($this->page) {
                    $slug = $this->page->translate($locale)->slug ?? '';

                    return $slug ? "/{$slug}" : null;
                }

                return null;
            case 'internal':
            case 'external':
                return $this->translate($locale)->url ?? null;
            default:
                return null;
        }
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function getIconUrl(): ?string
    {
        if ($this->icon) {
            return Storage::disk('public')->url($this->icon);
        }

        $media = $this->getFirstMedia('icon');

        return $media ? $media->getUrl() : null;
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->getIconUrl();
    }

    public function getIconAltUrl(): ?string
    {
        if ($this->icon_alt) {
            return Storage::disk('public')->url($this->icon_alt);
        }

        return null;
    }

    public function getIconAltUrlAttribute(): ?string
    {
        return $this->getIconAltUrl();
    }

    public function getNameAttribute(): ?string
    {
        return $this->translations->first()?->name ?? $this->translate()?->name;
    }

    protected static function booted(): void
    {
        static::deleting(function (MenuItem $menuItem) {
            $menuItem->children()->delete();
        });
    }
}
