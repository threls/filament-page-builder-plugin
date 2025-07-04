<?php

namespace Threls\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;
use Threls\FilamentPageBuilder\Enums\PageStatusEnum;

class Page extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $guarded = ['id'];

    public array $translatable = ['title', 'content'];

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
