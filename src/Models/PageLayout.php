<?php

namespace Threls\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function columns(): HasMany
    {
        return $this->hasMany(PageLayoutColumn::class)->orderBy('index');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
