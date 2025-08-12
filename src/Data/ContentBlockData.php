<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Data;
use Threls\FilamentPageBuilder\Enums\PageLayoutTypesEnum;

class ContentBlockData extends Data
{
    public function __construct(
        public string $type,
        public HeroSectionData|ImageGalleryData|HorizontalTickerData|
        BannerData|RichTextPageData|KeyValueSectionData|MapLocationData|
        ImageCardData|RelationshipData|VideoEmbedderData|DividerData|ImageAndRichTextData|LayoutSectionData $data,
        public ?string $column = null,
    )
    {
    }

    public static function fromArray(array $content): self
    {
        $type = $content['type'] ?? '';
        $data = $content['data'] ?? [];

        if (is_string($type) && str_starts_with($type, 'layout_section:')) {
            $parts = explode(':', $type, 2);
            $layoutIdFromType = isset($parts[1]) ? (int) $parts[1] : null;
            if ($layoutIdFromType && (! isset($data['layout_id']) || empty($data['layout_id']))) {
                $data['layout_id'] = $layoutIdFromType;
            }
            $content['type'] = 'layout_section';
        }

        $column = is_array($data) ? ($data['column'] ?? null) : null;
        if (is_array($data) && array_key_exists('column', $data)) {
            unset($data['column']);
        }

        return new self(
            type: $content['type'],
            data: self::returnData($content['type'], $data),
            column: $column,
        );
    }

    protected static function returnData(string $type, mixed $data)
    {
        return match ($type) {
            'layout_section' => LayoutSectionData::fromArray($data),
            PageLayoutTypesEnum::HERO_SECTION->value => HeroSectionData::fromArray($data),
            PageLayoutTypesEnum::IMAGE_GALLERY->value => ImageGalleryData::fromArray($data),
//            PageLayoutTypesEnum::HORIZONTAL_TICKER->value => HorizontalTickerData::fromArray($data),
            PageLayoutTypesEnum::BANNER->value => BannerData::from($data),
            PageLayoutTypesEnum::RICH_TEXT_PAGE->value => RichTextPageData::from($data),
            PageLayoutTypesEnum::KEY_VALUE_SECTION->value => KeyValueSectionData::fromArray($data),
//            PageLayoutTypesEnum::MAP_LOCATION->value => MapLocationData::fromArray($data),
//            PageLayoutTypesEnum::IMAGE_CARDS->value => ImageCardData::fromArray($data),
            PageLayoutTypesEnum::RELATIONSHIP_CONTENT->value => RelationshipData::from($data),
            PageLayoutTypesEnum::VIDEO_EMBEDDER->value => VideoEmbedderData::fromArray($data),
            PageLayoutTypesEnum::DIVIDER->value => new DividerData(),
            PageLayoutTypesEnum::IMAGE_AND_RICH_TEXT->value => ImageAndRichTextData::from($data),
        };
    }
}
