<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;
use Threls\FilamentPageBuilder\Models\RelationshipType;

#[MapName(SnakeCaseMapper::class)]
class RelationshipData extends Data
{
    /** @var array<string, array|null> */
    private static array $metaCache = [];

    public function __construct(
        public string $relationship,
        public array|Optional $meta = new Optional(),
    ) {}

    public static function fromArray(array $data): self
    {
        $relationship = (string) ($data['relationship'] ?? '');
        $meta = new Optional();
        if ($relationship !== '') {
            if (! array_key_exists($relationship, self::$metaCache)) {
                $record = RelationshipType::query()->where('handle', $relationship)->first();
                self::$metaCache[$relationship] = $record?->meta ?? null;
            }
            if (is_array(self::$metaCache[$relationship])) {
                $meta = self::$metaCache[$relationship];
            }
        }

        return new self(
            relationship: $relationship,
            meta: $meta,
        );
    }
}
