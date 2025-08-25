<?php

namespace Threls\FilamentPageBuilder\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Threls\FilamentPageBuilder\Models\PageLayout;
use Spatie\LaravelData\Optional;

#[MapName(SnakeCaseMapper::class)]
class LayoutSectionData extends Data
{
    /**
     * Simple per-request cache to avoid N+1 when serializing many layout sections.
     */
    private static array $layoutCache = [];
    public function __construct(
        public int $layout_id,
        #[DataCollectionOf(ColumnData::class)]
        public array $columns,
        public array|Optional $settings = new Optional(),
        // include layout settings in API payloads
        public array $layout_settings = [],
    ) {}


    public static function fromArray(array $data): self
    {
        $layoutId = (int) ($data['layout_id'] ?? 0);
        $persistedColumns = $data['columns'] ?? [];
        $columnsPayload = [];
        $rawSettings = $data['settings'] ?? null;
        $settings = is_array($rawSettings) ? $rawSettings : [];

        $layoutSettings = [];

        if ($layoutId > 0) {
            $layout = self::$layoutCache[$layoutId] ?? null;
            if ($layout === null) {
                $layout = PageLayout::with('columns')->find($layoutId);
                self::$layoutCache[$layoutId] = $layout;
            }
            if ($layout) {
                $layoutSettings = is_array($layout->settings) ? $layout->settings : [];
                // Build columns as objects with metadata and components (preserve column order)
                foreach ($layout->columns as $col) {
                    $entry = self::findPersistedEntry($persistedColumns, $col);
                    $columnComponents = self::mapBlocksToContent($entry);

                    // Push ColumnData with metadata and components (possibly empty)
                    $columnsPayload[] = new ColumnData(
                        id: $col->id,
                        key: $col->key ?: (string) $col->index,
                        index: $col->index,
                        settings: is_array($col->settings) ? $col->settings : [],
                        components: $columnComponents,
                    );
                }
            }
        }

        return new self(
            layout_id: $layoutId,
            columns: $columnsPayload,
            settings: ! empty($settings) ? $settings : new Optional(),
            layout_settings: $layoutSettings,
        );
    }

    /**
     * Locate persisted blocks for a given column using id or key.
     * @param array $persistedColumns
     * @param mixed $col
     * @return array|null
     */
    private static function findPersistedEntry(array $persistedColumns, $col): array|null
    {
        if (! is_array($persistedColumns)) {
            return null;
        }
        $idStr = isset($col->id) ? (string) $col->id : null;
        $keyStr = isset($col->key) && $col->key !== '' ? $col->key : null;

        if ($idStr !== null && array_key_exists($idStr, $persistedColumns)) {
            return $persistedColumns[$idStr];
        }
        if ($keyStr !== null && array_key_exists($keyStr, $persistedColumns)) {
            return $persistedColumns[$keyStr];
        }
        return null;
    }

    /**
     * Turn a raw builder entry array into an ordered list of ContentBlockData.
     * @param array|null $entry
     * @return array<int, ContentBlockData>
     */
    private static function mapBlocksToContent(?array $entry): array
    {
        $components = [];
        if (! is_array($entry)) {
            return $components;
        }
        $blocks = array_values($entry);
        foreach ($blocks as $blk) {
            if (! is_array($blk) || ! isset($blk['type'])) {
                continue;
            }
            $components[] = ContentBlockData::fromArray($blk);
        }
        return $components;
    }
}
