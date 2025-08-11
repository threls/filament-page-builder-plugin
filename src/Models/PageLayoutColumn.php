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
}
