<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ImageCardData extends Data
{
    public function __construct(
        public ?string $title,
        /** @var ImageCardItemData[] */
        public array $group
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            group: ImageCardItemData::collect($data['group']),
        );
    }
}
