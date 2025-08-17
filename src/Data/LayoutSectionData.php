<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class LayoutSectionData extends Data
{
    public function __construct(
        public int $layout_id,
        #[DataCollectionOf(ContentBlockData::class)]
        public array $items,
        public array $settings = [],
    ) {}


    public static function fromArray(array $data): self
    {
        return new self(
            layout_id: (int) ($data['layout_id'] ?? 0),
            items: ContentBlockData::collect($data['items'] ?? []),
            settings: $data['settings'] ?? [],
        );
    }
}
