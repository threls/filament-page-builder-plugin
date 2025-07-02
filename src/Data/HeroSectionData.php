<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class HeroSectionData extends Data
{
    public function __construct(
        public string | MediaData | null $image,
        public string | MediaData | null $sticker,
        public string $title,
        public ?string $subtitle,
        public ?string $buttonText,
        public ?string $buttonPath,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            image: $data['image'] ?? null,
            sticker: $data['sticker'] ?? null,
            title: $data['title'],
            subtitle: $data['subtitle'] ?? null,
            buttonText: $data['button-text'] ?? null,
            buttonPath: $data['button-path'] ?? null,
        );
    }
}
