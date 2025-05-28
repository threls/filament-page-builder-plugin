<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class HeroSectionData extends Data
{
    public function __construct(
        public string $image,
        public string $title,
        public ?string $subtitle,
        public ?string $buttonText,
        public ?string $buttonPath,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            image: $data['image'],
            title: $data['title'],
            subtitle: $data['subtitle'] ?? null,
            buttonText: $data['button-text'] ?? null,
            buttonPath: $data['button-path'] ?? null,
        );
    }
}