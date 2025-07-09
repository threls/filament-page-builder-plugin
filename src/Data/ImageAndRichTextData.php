<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ImageAndRichTextData extends Data
{
    public function __construct(
        public ?string $title,
        public string $image,
        public ?string $sticker,
        public ?string $backgroundImage,
        public string $content,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            image: $data['image'],
            sticker: $data['sticker'] ?? null,
            backgroundImage: $data['background_image'] ?? null,
            content: $data['content'],
        );
    }
}
