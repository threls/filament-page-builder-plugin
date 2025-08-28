<?php

namespace Threls\FilamentPageBuilder\Resources\PageResource\Support;

use Illuminate\Support\Str;

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

            // Composition blocks
            if (str_starts_with($rawType, 'composition:')) {
                // Convert to canonical type and persist composition_id in data
                $section['type'] = 'composition';
                $parts = explode(':', $rawType, 2);
                $compositionId = isset($parts[1]) ? (int) $parts[1] : null;

                $data = is_array($section['data'] ?? null) ? $section['data'] : [];
                if ($compositionId && empty($data['composition_id'])) {
                    $data['composition_id'] = $compositionId;
                }
                // Keep fields stable as an array for blueprint values, if present
                if (! isset($data['fields']) || ! is_array($data['fields'])) {
                    $data['fields'] = is_array($data['fields'] ?? null) ? $data['fields'] : [];
                }
                $section['data'] = $data;
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

                // Stable instance key
                $instanceKey = $section['data']['instance_id'] ?? ($section['data']['instance_key'] ?? null);
                if (! $instanceKey) {
                    $instanceKey = (string) Str::uuid();
                }

                $section['data'] = [
                    'blueprint_version_id' => $versionId ? (int) $versionId : null,
                    'instance_key' => $instanceKey,
                    'fields' => $collected,
                ];
            }
        }

        return $state;
    }

}
