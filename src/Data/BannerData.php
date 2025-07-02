<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class BannerData extends Data
{
    public function __construct(
        public ?string $text,
        public string|MediaData|null $image,
        public ?string $title,
        public ?string $description,
        public ?string $buttonText,
        public ?string $buttonPath,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            text: $data['text'] ?? null,
            image: $data['image'] ?? null,
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            buttonText: $data['button-text'] ?? null,
            buttonPath: $data['button-path'] ?? null,
        );
    }
}
