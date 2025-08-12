<?php

namespace Threls\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageLayoutColumn extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(PageLayout::class, 'page_layout_id');
    }

    protected static function booted(): void
    {
        static::creating(function (PageLayoutColumn $column) {
            if (is_null($column->index)) {
                $max = static::where('page_layout_id', $column->page_layout_id)->max('index');
                $column->index = ((int) ($max ?? 0)) + 1;
            }
        });
    }
}
