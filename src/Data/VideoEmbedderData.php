<?php

namespace Threls\FilamentPageBuilder\Data;


use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class VideoEmbedderData extends Data
{
    public function __construct(
        public ?string $title,
        public ?string $video,
        public ?string $externalUrl,
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            video: $data['video'] ?? null,
            externalUrl: $data['external_url'] ?? null,
        );
    }
}
