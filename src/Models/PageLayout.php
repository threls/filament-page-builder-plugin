<?php

namespace Threls\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Threls\FilamentPageBuilder\Support\SettingsNormalizer;

class PageLayout extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PageLayout $layout) {
            if (is_array($layout->settings)) {
                $layout->settings = SettingsNormalizer::normalizeLayoutSettings($layout->settings);
            }
        });
    }

    public function columns(): HasMany
    {
        return $this->hasMany(PageLayoutColumn::class)->orderBy('index');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
