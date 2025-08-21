<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Data;
use Threls\FilamentPageBuilder\Enums\PageLayoutTypesEnum;
use Spatie\LaravelData\Optional;
use Threls\FilamentPageBuilder\Models\BlueprintVersion;

class ContentBlockData extends Data
{
    /** @var array<int, string|null> */
    private static array $bvHandleCache = [];
    public function __construct(
        public string $type,
        public HeroSectionData|ImageGalleryData|
        BannerData|RichTextPageData|KeyValueSectionData|RelationshipData|VideoEmbedderData|DividerData|ImageAndRichTextData|LayoutSectionData|BlueprintComponentData|array $data,
        public int|Optional $blueprint_version_id = new Optional(),
        public array|Optional $column = new Optional(),
        public array|Optional $settings = new Optional(),
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

        // Normalize blueprint_component:<id> to canonical type + data shape
        if (is_string($type) && str_starts_with($type, 'blueprint_component:')) {
            $parts = explode(':', $type, 2);
            $bvIdFromType = isset($parts[1]) ? (int) $parts[1] : null;
            if ($bvIdFromType && (! isset($data['blueprint_version_id']) || empty($data['blueprint_version_id']))) {
                $data['blueprint_version_id'] = $bvIdFromType;
            }
            $content['type'] = 'blueprint_component';
        }

        $column = is_array($data) ? ($data['column'] ?? null) : null;
        if (is_array($data) && array_key_exists('column', $data)) {
            unset($data['column']);
        }

        // Blueprint components: change outward type to blueprint handle and lift blueprint_version_id to top level
        if ($content['type'] === 'blueprint_component') {
            $bvId = isset($data['blueprint_version_id']) ? (int) $data['blueprint_version_id'] : null;
            $handle = null;
            if ($bvId) {
                if (array_key_exists($bvId, self::$bvHandleCache)) {
                    $handle = self::$bvHandleCache[$bvId];
                } else {
                    $bv = BlueprintVersion::with('blueprint')->find($bvId);
                    $handle = $bv?->blueprint?->handle;
                    self::$bvHandleCache[$bvId] = $handle;
                }
            }
            // Unwrap fields
            $fields = is_array($data['fields'] ?? null) ? $data['fields'] : [];
            if (is_array($fields) && array_key_exists('fields', $fields) && is_array($fields['fields'])) {
                $fields = $fields['fields'];
            }

            return new self(
                type: $handle ?: 'blueprint_component',
                data: $fields,
                blueprint_version_id: $bvId ?? new Optional(),
                column: is_array($column) ? $column : new Optional(),
                settings: (! empty($content['settings']) && is_array($content['settings'])) ? $content['settings'] : new Optional(),
            );
        }

        return new self(
            type: $content['type'],
            data: self::returnData($content['type'], $data),
            column: is_array($column) ? $column : new Optional(),
            settings: (! empty($content['settings']) && is_array($content['settings'])) ? $content['settings'] : new Optional(),
        );
    }

    protected static function returnData(string $type, mixed $data)
    {
        return match ($type) {
            'layout_section' => LayoutSectionData::fromArray($data),
            PageLayoutTypesEnum::HERO_SECTION->value => HeroSectionData::fromArray($data),
            PageLayoutTypesEnum::IMAGE_GALLERY->value => ImageGalleryData::fromArray($data),
            PageLayoutTypesEnum::BANNER->value => BannerData::from($data),
            PageLayoutTypesEnum::RICH_TEXT_PAGE->value => RichTextPageData::from($data),
            PageLayoutTypesEnum::KEY_VALUE_SECTION->value => KeyValueSectionData::fromArray($data),
            PageLayoutTypesEnum::RELATIONSHIP_CONTENT->value => RelationshipData::fromArray($data),
            PageLayoutTypesEnum::VIDEO_EMBEDDER->value => VideoEmbedderData::fromArray($data),
            PageLayoutTypesEnum::DIVIDER->value => new DividerData(),
            PageLayoutTypesEnum::IMAGE_AND_RICH_TEXT->value => ImageAndRichTextData::from($data),
        };
    }
}
