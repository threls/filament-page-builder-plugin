<?php

namespace Threls\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Threls\FilamentPageBuilder\Support\SettingsNormalizer;

class PageLayoutColumn extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PageLayoutColumn $column) {
            if (is_array($column->settings)) {
                $column->settings = SettingsNormalizer::normalizeColumnSettings($column->settings);
            }
        });

        static::creating(function (PageLayoutColumn $column) {
            if (is_null($column->index)) {
                $max = static::where('page_layout_id', $column->page_layout_id)->max('index');
                $column->index = ((int) ($max ?? 0)) + 1;
            }
        });
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(PageLayout::class, 'page_layout_id');
    }

    
}
