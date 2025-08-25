<?php

namespace Threls\FilamentPageBuilder\Resources\PageResource\Support;

use Threls\FilamentPageBuilder\Enums\BlueprintFieldTypeEnum;

class PageBuilderDehydrateUtil
{
    /**
     * Canonicalize builder state before persisting to the database.
     */
    public static function dehydrateBuilderStateForSave(mixed $state): mixed
    {
        if (! is_array($state)) {
            return $state;
        }

        foreach ($state as &$section) {
            if (! is_array($section)) {
                continue;
            }

            $rawType = $section['type'] ?? '';
            if (! is_string($rawType)) {
                continue;
            }

            // Layout sections
            if (str_starts_with($rawType, 'layout_section:')) {
                $section['type'] = 'layout_section';

                $layoutId = $section['data']['layout_id'] ?? null;
                $layoutModel = $layoutId ? PageBuilderUtils::getLayoutById((int) $layoutId) : null;

                $columnsToPersist = [];
                if ($layoutModel) {
                    $layoutColumns = $section['data']['columns'] ?? [];
                    foreach ($layoutColumns as $id => $layoutColumn) {
                        $idStr = (string) $id;
                        $value = $layoutColumn ?? [];
                        $columnsToPersist[$idStr] = is_array($value) ? array_values($value) : [];
                    }
                }

                $layoutData = [
                    'layout_id' => $layoutId,
                    'columns' => $columnsToPersist,
                ];
                if (isset($section['data']['settings'])) {
                    $layoutData['settings'] = $section['data']['settings'];
                }
                $section['data'] = $layoutData;
            } elseif (str_starts_with($rawType, 'blueprint_version:')) {
                // Blueprint components
                $section['type'] = 'blueprint_component';

                // Extract version id directly from the block type (blueprint_version:<version_id>)
                $parts = explode(':', $rawType, 2);
                $versionId = isset($parts[1]) ? (int) $parts[1] : null;

                $collected = [];
                if ($versionId) {
                    $version = PageBuilderUtils::getBlueprintVersionById((int) $versionId);
                    $schema = $version?->schema ?? [];
                    $schemaFields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
                    foreach ($schemaFields as $fieldDef) {
                        if (! is_array($fieldDef)) {
                            continue;
                        }
                        $fKey = $fieldDef['key'] ?? null;
                        if (! $fKey) {
                            continue;
                        }
                        $val = $section['data'][$fKey] ?? ($section['data']['fields'][$fKey] ?? null);
                        $collected[$fKey] = $val;
                    }
                }

                $section['data'] = [
                    'blueprint_version_id' => $versionId ? (int) $versionId : null,
                    'fields' => $collected,
                ];
            }
        }

        return $state;
    }
}
