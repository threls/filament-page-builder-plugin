<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Data;
use Threls\FilamentPageBuilder\Enums\PageLayoutTypesEnum;

class ContentBlockData extends Data
{
    public function __construct(
        public string $type,
        public HeroSectionData|ImageGalleryData|HorizontalTickerData|BannerData|RichTextPageData|KeyValueSectionData|MapLocationData|ImageCardData|RelationshipData $data,
    ) {}

    public static function fromArray(array $content): self
    {
        return new self(
            type: $content['type'],
            data: self::returnData($content['type'], $content['data']),
        );
    }

    protected static function returnData(string $type, mixed $data)
    {
        return match ($type) {
            PageLayoutTypesEnum::HERO_SECTION->value => HeroSectionData::fromArray($data),
            PageLayoutTypesEnum::IMAGE_GALLERY->value => ImageGalleryData::fromArray($data),
            PageLayoutTypesEnum::HORIZONTAL_TICKER->value => HorizontalTickerData::fromArray($data),
            PageLayoutTypesEnum::BANNER->value => BannerData::from($data),
            PageLayoutTypesEnum::RICH_TEXT_PAGE->value => RichTextPageData::from($data),
            PageLayoutTypesEnum::KEY_VALUE_SECTION->value => KeyValueSectionData::fromArray($data),
            PageLayoutTypesEnum::MAP_LOCATION->value => MapLocationData::fromArray($data),
            PageLayoutTypesEnum::IMAGE_CARDS->value => ImageCardData::fromArray($data),
            PageLayoutTypesEnum::RELATIONSHIP_CONTENT->value => RelationshipData::from($data),
        };
    }
}