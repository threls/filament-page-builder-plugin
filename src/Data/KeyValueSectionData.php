<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Threls\FilamentPageBuilder\Enums\PageGridStyleEnum;

#[MapName(SnakeCaseMapper::class)]
class KeyValueSectionData extends Data
{
    public function __construct(
        public ?string $variant,
        /** @var KeyValueItemData[] */
        public array $group,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            variant: $data['variant'] ?? PageGridStyleEnum::NORMAL_GRID->value,
            group: KeyValueItemData::collect($data['group']),
        );
    }
}
