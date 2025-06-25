<?php

namespace Threls\FilamentPageBuilder\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Threls\FilamentPageBuilder\Models\Page;

trait HasPageBuilder
{
    public function pages(): MorphMany
    {
        return $this->morphMany(Page::class, 'resource');
    }

    public function page(): MorphMany
    {
        return $this->pages();
    }
}