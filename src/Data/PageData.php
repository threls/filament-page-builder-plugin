<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Threls\FilamentPageBuilder\Models\Page;

#[MapName(SnakeCaseMapper::class)]
class PageData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        #[DataCollectionOf(ContentBlockData::class)]
        public array $content,
    ) {}

    public static function fromModel(Page $page): self
    {
        return new self(
            id: $page->id,
            title: $page->title,
            slug: $page->slug,
            content: ContentBlockData::collect($page->content),
        );
    }
}