<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Data;

class HorizontalTickerData extends Data
{
    public function __construct(
        public ?string $title,
        /** @var TickerGroupItemData[] */
        public array $group,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            group: TickerGroupItemData::collect($data['group']),
        );
    }
}
