<?php

namespace Threls\FilamentPageBuilder\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Threls\FilamentPageBuilder\Data\MediaData;

class ContentWithMedia implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (!$model instanceof HasMedia) {
            return json_decode($value, true) ?? [];
        }

        $content = json_decode($value, true) ?? [];
        
        // Load media for the model
        $model->load('media');
        
        return $this->transformContent($content, $model);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return json_encode($value);
    }

    protected function transformContent(array|string $content, HasMedia $model): array|string|MediaData
    {
        if (is_string($content)) {
            return $this->transformString($content, $model);
        }

        $transformed = [];
        
        foreach ($content as $key => $value) {
            if (is_array($value)) {
                // Handle arrays of images
                if ($this->isImageField($key) && $this->isSequentialArray($value)) {
                    $transformed[$key] = array_map(fn($item) => $this->transformString($item, $model), $value);
                } else {
                    $transformed[$key] = $this->transformContent($value, $model);
                }
            } elseif (is_string($value) && $this->isImageField($key)) {
                $transformed[$key] = $this->transformString($value, $model);
            } else {
                $transformed[$key] = $value;
            }
        }

        return $transformed;
    }

    protected function transformString(string $value, HasMedia $model): string|MediaData
    {
        // Check if it's a media ID reference (e.g., "media:123")
        if (Str::startsWith($value, 'media:')) {
            $mediaId = (int) Str::after($value, 'media:');
            $media = $model->media->firstWhere('id', $mediaId);
            
            if ($media) {
                return MediaData::fromMedia($media);
            }
        }

        // Check if it's a path that matches a media file
        $media = $model->media->first(function ($media) use ($value) {
            return Str::endsWith($media->getUrl(), $value) || 
                   Str::endsWith($media->getPath(), $value);
        });

        if ($media) {
            return MediaData::fromMedia($media);
        }

        // Return as-is if not found (could be an external URL or temporary path)
        return MediaData::fromPath($value);
    }

    protected function isImageField(string $key): bool
    {
        $imageFields = ['image', 'images', 'icon', 'logo', 'avatar', 'photo', 'picture', 'thumbnail'];
        
        return in_array($key, $imageFields) || 
               Str::endsWith($key, '_image') || 
               Str::endsWith($key, '_images');
    }

    protected function isSequentialArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }
}