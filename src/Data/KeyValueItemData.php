<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class KeyValueItemData extends Data
{
    public function __construct(
        public string $title,
        public string $key,
        public ?string $description,
        public ?string $hint,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            key: $data['key'] ?? null,
            description: $data['description'] ?? null,
            hint: $data['hint'] ?? null,
        );
    }
}
