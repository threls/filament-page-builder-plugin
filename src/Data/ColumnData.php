<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ColumnData extends Data
{
    public function __construct(
        public int $id,
        public string $key,
        public int $index,
        public array $settings,
        #[DataCollectionOf(ContentBlockData::class)]
        public array $components,
    ) {}
}
