<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Data;

class MapLocationData extends Data
{
    public function __construct(
        public ?string $title,
        public string $latitude,
        public string $longitude,
        public ?string $address,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
            address: $data['address'] ?? null,
        );
    }
}