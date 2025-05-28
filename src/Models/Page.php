<?php

namespace Threls\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Threls\FilamentPageBuilder\Enums\PageStatusEnum;

class Page extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function resource(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'relationships' => 'array',
            'status' => PageStatusEnum::class,
        ];
    }
}
