<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ImageGalleryData extends Data
{
    public function __construct(
        public string $text,
        /** @var string[] */
        public array $images,
        public ?string $buttonText,
        public ?string $buttonPath,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            text: $data['text'],
            images: $data['images'],
            buttonText: $data['button-text'] ?? null,
            buttonPath: $data['button-path'] ?? null,
        );
    }
}