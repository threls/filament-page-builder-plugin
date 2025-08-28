<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class BlueprintComponentData extends Data
{
    public function __construct(
        public ?int $blueprintVersionId,
        public array $fields = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $fields = is_array($data['fields'] ?? null) ? $data['fields'] : [];
        if (is_array($fields) && array_key_exists('fields', $fields) && is_array($fields['fields'])) {
            $fields = $fields['fields'];
        }

        return new self(
            blueprintVersionId: isset($data['blueprint_version_id']) ? (int) $data['blueprint_version_id'] : null,
            fields: $fields,
        );
    }
}
