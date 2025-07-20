<?php

namespace Threls\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Threls\FilamentPageBuilder\Enums\PageStatusEnum;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class Page extends Model implements HasMedia, TranslatableContract
{
    use InteractsWithMedia;
    use SoftDeletes;
    use Translatable;

    protected $guarded = ['id'];

    public array $translatedAttributes = ['content'];

    public function resource(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'status' => PageStatusEnum::class,
        ];
    }
}
