<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Data;

class TickerGroupItemData extends Data
{
    public function __construct(
        public ?string $title,
        public ?string $description,
        /** @var string[] */
        public ?array $images,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            images: $data['images'] ?? null,
        );
    }
}
