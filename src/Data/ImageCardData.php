<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ImageCardData extends Data
{
    public function __construct(
        /** @var ImageCardItemData[] */
        public array $group
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            group: ImageCardItemData::collect($data['group']),
        );
    }
}
