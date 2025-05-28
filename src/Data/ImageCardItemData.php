<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ImageCardItemData extends Data
{
    public function __construct(
        public ?string $text,
        public string $image,
        public ?string $buttonText,
        public ?string $buttonPath,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            text: $data['text'] ?? null,
            image: $data['image'] ?? null,
            buttonText: $data['button-text'] ?? $data['button_text'] ?? null,
            buttonPath: $data['button-path'] ?? $data['button_path'] ?? null,
        );
    }
}