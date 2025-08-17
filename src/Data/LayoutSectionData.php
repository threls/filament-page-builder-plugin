<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Threls\FilamentPageBuilder\Models\PageLayout;

#[MapName(SnakeCaseMapper::class)]
class LayoutSectionData extends Data
{
    /**
     * Simple per-request cache to avoid N+1 when serializing many layout sections.
     */
    private static array $layoutCache = [];
    public function __construct(
        public int $layout_id,
        public array $items,
        public array $settings = [],
        // Added: include layout & columns settings in API payloads
        public array $layout_settings = [],
        public array $columns = [],
    ) {}


    public static function fromArray(array $data): self
    {
        $layoutId = (int) ($data['layout_id'] ?? 0);
        $persistedColumns = $data['columns'] ?? [];
        $items = [];
        $rawSettings = $data['settings'] ?? null;
        $settings = is_array($rawSettings) ? $rawSettings : [];

        $layoutSettings = [];
        $columnsOut = [];

        if ($layoutId > 0) {
            $layout = self::$layoutCache[$layoutId] ?? null;
            if ($layout === null) {
                $layout = PageLayout::with('columns')->find($layoutId);
                self::$layoutCache[$layoutId] = $layout;
            }
            if ($layout) {
                $layoutSettings = is_array($layout->settings) ? $layout->settings : [];
                $columnsOut = [];
                foreach ($layout->columns as $col) {
                    $columnsOut[] = [
                        'id' => $col->id,
                        'key' => $col->key ?: (string) $col->index,
                        'index' => $col->index,
                        'settings' => is_array($col->settings) ? $col->settings : [],
                    ];
                }
                // Build items in layout index order, mapping via column id with fallback to key, include nulls for empty columns
                foreach ($layout->columns as $col) {
                    $idStr = (string) $col->id;
                    $keyStr = $col->key ?: null;
                    $entry = null;
                    if (is_array($persistedColumns)) {
                        if (array_key_exists($idStr, $persistedColumns)) {
                            $entry = $persistedColumns[$idStr];
                        } elseif ($keyStr !== null && array_key_exists($keyStr, $persistedColumns)) {
                            $entry = $persistedColumns[$keyStr];
                        }
                    }
                    $first = is_array($entry) ? (array_values($entry)[0] ?? null) : null;
                    if (is_array($first) && isset($first['type'])) {
                        $items[] = ContentBlockData::fromArray($first);
                    } else {
                        $items[] = null; // include null placeholder when no content
                    }
                }
            }
        }

        return new self(
            layout_id: $layoutId,
            items: $items,
            settings: $settings,
            layout_settings: $layoutSettings,
            columns: $columnsOut,
        );
    }
}
