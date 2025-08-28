<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Data;
use Threls\FilamentPageBuilder\Enums\PageLayoutTypesEnum;
use Spatie\LaravelData\Optional;
use Threls\FilamentPageBuilder\Resources\PageResource\Support\PageBuilderUtils;
use Threls\FilamentPageBuilder\Models\Composition;
use Threls\FilamentPageBuilder\Models\PageLayout;

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

        if (! is_string($type)) {
            throw new \Exception('Invalid type: ' . $type);
        }

        // Runtime resolution for composition blocks: convert to canonical layout_section for API output
        if ($type === 'composition') {
            $fields = is_array($data['fields'] ?? null) ? $data['fields'] : [];
            // composition_id can be stored either at data.composition_id or under data.fields.composition_id
            $compId = (int) ($data['composition_id'] ?? ($fields['composition_id'] ?? 0));
            // Fallback to id embedded in the original type if present
            if (! $compId && str_starts_with($type, 'composition:')) {
                $parts = explode(':', $type, 2);
                $compId = isset($parts[1]) ? (int) $parts[1] : 0;
            }
            $counters = [];
            $orderCounters = [];
            $layoutData = self::compositionToLayoutSectionArray($compId, $fields, $counters, $orderCounters);
            $data = $layoutData;
            $content['type'] = 'layout_section';
        }


        $column = is_array($data) ? ($data['column'] ?? null) : null;
        if (is_array($data) && array_key_exists('column', $data)) {
            unset($data['column']);
        }

        // Blueprint components: change outward type to blueprint handle and lift blueprint_version_id to top level
        if ($type === 'blueprint_component') {
            // Reuse the dedicated DTO to normalize fields shape
            $bp = BlueprintComponentData::fromArray(is_array($data) ? $data : []);
            $bvId = $bp->blueprintVersionId ?? null;

            $handle = null;
            if ($bvId) {
                if (array_key_exists($bvId, self::$bvHandleCache)) {
                    $handle = self::$bvHandleCache[$bvId];
                } else {
                    $bv = PageBuilderUtils::getBlueprintVersionById($bvId);
                    $handle = $bv?->blueprint?->handle;
                    self::$bvHandleCache[$bvId] = $handle;
                }
            }

            return new self(
                type: $handle ?: 'blueprint_component',
                data: $bp->fields,
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

    /**
     * Build a layout_section payload from a composition id and page fields.
     * Uses the first (and only) root layout_section from the composition payload.
     */
    private static function compositionToLayoutSectionArray(int $compositionId, array $pageFields, array &$counters = [], array &$orderCounters = []): array
    {
        $empty = ['layout_id' => 0, 'columns' => []];
        if ($compositionId <= 0) {
            return $empty;
        }

        $composition = Composition::query()->find($compositionId);
        $payload = is_array($composition?->payload ?? null) ? $composition->payload : [];
        if (empty($payload)) {
            return $empty;
        }

        // Find the first root layout_section in the composition payload
        $root = null;
        foreach ($payload as $node) {
            if (! is_array($node)) {
                continue;
            }
            $t = (string) ($node['type'] ?? '');
            if ($t === 'layout_section' || str_starts_with($t, 'layout_section:')) {
                $root = $node;
                break;
            }
        }
        if (! $root) {
            return $empty;
        }

        // Extract layout id
        $layoutId = self::extractLayoutId($root);

        $result = [
            'layout_id' => $layoutId,
            'columns' => [],
        ];

        $columns = is_array(($root['data']['columns'] ?? null)) ? $root['data']['columns'] : [];
        $colIdToKey = self::buildColIdToKeyMap($layoutId);
        $blueprintsMap = is_array(($pageFields['blueprints'] ?? null)) ? $pageFields['blueprints'] : [];

        foreach ($columns as $colId => $blocks) {
            $colKey = (string) $colId;
            $altColKey = $colIdToKey[$colKey] ?? null;
            $resultCol = [];

            foreach ((array) $blocks as $blk) {
                if (! is_array($blk)) {
                    continue;
                }
                $bType = (string) ($blk['type'] ?? '');

                // Blueprint blocks: attach page field values using instance_key
                if ($bType === 'blueprint_component' || str_starts_with($bType, 'blueprint_version:')) {
                    $bp = self::buildBlueprintComponent($blk, $colKey, $altColKey, $blueprintsMap, $counters, $orderCounters);
                    if ($bp !== null) {
                        $resultCol[] = $bp;
                        continue;
                    }
                }

                // Nested layout section: recurse
                if ($bType === 'layout_section' || str_starts_with($bType, 'layout_section:')) {
                    $nested = self::compositionNestedLayoutFromNode($blk, $blueprintsMap, $counters, $orderCounters);
                    if ($nested) {
                        $resultCol[] = [
                            'type' => 'layout_section',
                            'data' => $nested,
                        ];
                    }
                }
            }

            $result['columns'][$colKey] = $resultCol;
        }

        return $result;
    }

    /**
     * Build layout_section data for a nested layout node using the page blueprints map.
     */
    private static function compositionNestedLayoutFromNode(array $node, array $blueprintsMap, array &$counters, array &$orderCounters): array|null
    {
        $layoutId = self::extractLayoutId($node);
        if (! $layoutId) {
            return null;
        }

        $out = [
            'layout_id' => $layoutId,
            'columns' => [],
        ];

        $columns = is_array(($node['data']['columns'] ?? null)) ? $node['data']['columns'] : [];
        $colIdToKey = self::buildColIdToKeyMap($layoutId);
        foreach ($columns as $colId => $blocks) {
            $colKey = (string) $colId;
            $altColKey = $colIdToKey[$colKey] ?? null;
            $colOut = [];
            foreach ((array) $blocks as $blk) {
                if (! is_array($blk)) {
                    continue;
                }
                $bType = (string) ($blk['type'] ?? '');
                if ($bType === 'blueprint_component' || str_starts_with($bType, 'blueprint_version:')) {
                    $bp = self::buildBlueprintComponent($blk, $colKey, $altColKey, $blueprintsMap, $counters, $orderCounters);
                    if ($bp !== null) {
                        $colOut[] = $bp;
                        continue;
                    }
                }
                if ($bType === 'layout_section' || str_starts_with($bType, 'layout_section:')) {
                    $nested = self::compositionNestedLayoutFromNode($blk, $blueprintsMap, $counters, $orderCounters);
                    if ($nested) {
                        $colOut[] = [
                            'type' => 'layout_section',
                            'data' => $nested,
                        ];
                    }
                }
            }
            $out['columns'][$colKey] = $colOut;
        }

        return $out;
    }

    /**
     * Extract a layout_id from a layout_section node, supporting both
     * type "layout_section:<id>" and data.layout_id.
     */
    private static function extractLayoutId(array $node): int
    {
        $layoutId = null;
        $rawType = (string) ($node['type'] ?? '');
        if (str_starts_with($rawType, 'layout_section:')) {
            $parts = explode(':', $rawType, 2);
            $layoutId = isset($parts[1]) ? (int) $parts[1] : null;
        }
        if (! $layoutId) {
            $layoutId = (int) ($node['data']['layout_id'] ?? 0);
        }
        return (int) $layoutId;
    }

    /**
     * Build a map of column id => key (or index) for a given layout.
     * This helps us resolve blueprint field values that may be stored by key.
     *
     * @return array<string, string>
     */
    private static function buildColIdToKeyMap(int $layoutId): array
    {
        $map = [];
        if ($layoutId <= 0) {
            return $map;
        }
        $layoutModel = PageLayout::with('columns')->find($layoutId);
        if (! $layoutModel) {
            return $map;
        }
        foreach ($layoutModel->columns as $colModel) {
            $map[(string) $colModel->id] = (string) ($colModel->key ?: $colModel->index);
        }
        return $map;
    }

    /**
     * Build a canonical blueprint_component block array with fields resolved
     * from the page blueprint values using column + instance key mapping.
     *
     * @return array|null
     */
    private static function buildBlueprintComponent(
        array $blk,
        string $colKey,
        ?string $altColKey,
        array $blueprintsMap,
        array &$counters,
        array &$orderCounters
    ): ?array {
        $bType = (string) ($blk['type'] ?? '');
        $versionId = null;
        if (str_starts_with($bType, 'blueprint_version:')) {
            $parts = explode(':', $bType, 2);
            $versionId = isset($parts[1]) ? (int) $parts[1] : null;
        }
        if (! $versionId) {
            $versionId = (int) ($blk['data']['blueprint_version_id'] ?? 0);
        }

        $instanceKey = (string) ($blk['data']['instance_key'] ?? ($blk['data']['instance_id'] ?? ''));
        if ($instanceKey === '') {
            // Mirror UI behavior: assign per-column incremental keys when missing
            $counters[$colKey] = isset($counters[$colKey]) ? ($counters[$colKey] + 1) : 1;
            $instanceKey = (string) $counters[$colKey];
        }

        $values = [];
        if ($instanceKey !== '' && isset($blueprintsMap[$colKey]) && isset($blueprintsMap[$colKey][$instanceKey])) {
            $values = is_array($blueprintsMap[$colKey][$instanceKey]) ? $blueprintsMap[$colKey][$instanceKey] : [];
        } elseif ($instanceKey !== '' && $altColKey !== null && isset($blueprintsMap[$altColKey]) && isset($blueprintsMap[$altColKey][$instanceKey])) {
            $values = is_array($blueprintsMap[$altColKey][$instanceKey]) ? $blueprintsMap[$altColKey][$instanceKey] : [];
        }

        // Ordered fallbacks per column key
        if (empty($values) && isset($blueprintsMap[$colKey]) && is_array($blueprintsMap[$colKey])) {
            $ordered = array_values($blueprintsMap[$colKey]);
            $idx = $orderCounters[$colKey] ?? 0;
            if (isset($ordered[$idx]) && is_array($ordered[$idx])) {
                $values = $ordered[$idx];
            }
            $orderCounters[$colKey] = $idx + 1;
        } elseif (empty($values) && $altColKey !== null && isset($blueprintsMap[$altColKey]) && is_array($blueprintsMap[$altColKey])) {
            $ordered = array_values($blueprintsMap[$altColKey]);
            $idx = $orderCounters[$altColKey] ?? 0;
            if (isset($ordered[$idx]) && is_array($ordered[$idx])) {
                $values = $ordered[$idx];
            }
            $orderCounters[$altColKey] = $idx + 1;
        }

        return [
            'type' => 'blueprint_component',
            'data' => [
                'blueprint_version_id' => $versionId,
                'fields' => $values,
            ],
        ];
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
            default => is_array($data) ? $data : [],
        };
    }
}
