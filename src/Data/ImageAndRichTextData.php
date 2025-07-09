<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Data;

class ImageAndRichTextData extends Data
{
    public function __construct(
        public string $title,
        public string $image,
        public string $content,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            image: $data['image'],
            content: $data['content'],
        );
    }
}
