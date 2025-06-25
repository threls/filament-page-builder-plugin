<?php

namespace Threls\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;
use Threls\FilamentPageBuilder\Casts\ContentWithMedia;
use Threls\FilamentPageBuilder\Enums\PageStatusEnum;
use Threls\FilamentPageBuilder\Services\PageImageProcessor;

class Page extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $guarded = ['id'];

    public array $translatable = ['title', 'slug', 'content'];

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

    public function getContentWithMediaAttribute(): array
    {
        $content = $this->content ?? [];

        if ($this->translatable && in_array('content', $this->translatable)) {
            $processedContent = [];
            foreach ($this->getTranslations('content') as $locale => $localeContent) {
                $this->load('media');
                $processedContent[$locale] = (new ContentWithMedia)->get($this, 'content', json_encode($localeContent), []);
            }

            return $processedContent;
        }

        return (new ContentWithMedia)->get($this, 'content', json_encode($content), []);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('page-builder')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->fit(Fit::Crop, 300, 300)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->fit(Fit::Crop, 800, 600)
            ->nonQueued();

        $this->addMediaConversion('large')
            ->fit(Fit::Max, 1920, 1080)
            ->nonQueued();
    }

    protected static function booted(): void
    {
        static::saving(function (self $page) {
            if ($page->isDirty('content')) {
                $imageProcessor = app(PageImageProcessor::class);
                $locales = config('filament-language-switch.locales', ['en' => 'English']);

                $processedContent = $imageProcessor->processAllLocales(
                    $page->getTranslations('content'),
                    $page,
                    $locales
                );

                foreach ($processedContent as $locale => $content) {
                    $page->setTranslation('content', $locale, $content);
                }
            }
        });
    }
}
