<?php

namespace Threls\FilamentPageBuilder\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PageImageProcessor
{
    protected string $tempPath = 'temp/page-builder/';

    protected string $disk;

    public function __construct()
    {
        $this->disk = config('filament-page-builder.media_disk', 'public');
    }

    public function processContent(array | string $content, HasMedia $model): array | string
    {
        if (is_string($content)) {
            return $this->processString($content, $model);
        }

        return $this->processArray($content, $model);
    }

    protected function processArray(array $content, HasMedia $model): array
    {
        $processed = [];

        foreach ($content as $key => $value) {
            if (is_array($value)) {
                $processed[$key] = $this->processArray($value, $model);
            } elseif (is_string($value) && $this->isImageField($key) && $this->isTemporaryImage($value)) {
                $processed[$key] = $this->processImage($value, $model);
            } else {
                $processed[$key] = $value;
            }
        }

        return $processed;
    }

    protected function processString(string $value, HasMedia $model): string
    {
        if ($this->isTemporaryImage($value)) {
            $media = $this->processImage($value, $model);

            return $media?->getUrl() ?? $value;
        }

        return $value;
    }

    protected function processImage(string $path, HasMedia $model): ?Media
    {
        if (! $this->isTemporaryImage($path)) {
            return null;
        }

        $tempDisk = Storage::disk($this->disk);

        if (! $tempDisk->exists($path)) {
            return null;
        }

        $fileName = basename($path);
        $tempFullPath = $tempDisk->path($path);

        $media = $model->addMedia($tempFullPath)
            ->usingName(pathinfo($fileName, PATHINFO_FILENAME))
            ->usingFileName($fileName)
            ->toMediaCollection('page-builder');

        $tempDisk->delete($path);

        return $media;
    }

    protected function isTemporaryImage(string $path): bool
    {
        return Str::startsWith($path, $this->tempPath);
    }

    protected function isImageField(string $key): bool
    {
        $imageFields = ['image', 'images', 'icon', 'logo', 'avatar', 'photo', 'picture', 'thumbnail'];

        return in_array($key, $imageFields) ||
               Str::endsWith($key, '_image') ||
               Str::endsWith($key, '_images');
    }

    public function processAllLocales(array $translatableContent, HasMedia $model, array $locales): array
    {
        $processed = [];

        foreach ($locales as $locale => $label) {
            if (isset($translatableContent[$locale])) {
                $processed[$locale] = $this->processContent($translatableContent[$locale], $model);
            }
        }

        return $processed;
    }
}
