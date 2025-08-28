<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[MapName(SnakeCaseMapper::class)]
class MediaData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $fileName,
        public string $url,
        public string $extension,
    ) {}

    public static function fromMedia(Media $media): self
    {
        return new self(
            id: $media->id,
            name: $media->name,
            fileName: $media->file_name,
            url: $media->getUrl(),
            extension: $media->extension ?? pathinfo($media->file_name, PATHINFO_EXTENSION),
        );
    }

    public static function fromPath(string $path): self
    {
        $fileName = basename($path);

        return new self(
            id: 0,
            name: pathinfo($fileName, PATHINFO_FILENAME),
            fileName: $fileName,
            url: $path,
            extension: pathinfo($fileName, PATHINFO_EXTENSION),
        );
    }
}
