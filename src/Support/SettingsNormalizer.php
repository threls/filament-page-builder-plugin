<?php

namespace Threls\FilamentPageBuilder\Support;

class SettingsNormalizer
{
    /**
     * Assign 1-based index to each column and sync columns_count from repeater length.
     */
    public static function assignColumns(array $data): array
    {
        $columns = array_values($data['columns'] ?? []);
        foreach ($columns as $i => $col) {
            $columns[$i]['index'] = $i + 1;
        }
        $data['columns'] = $columns;
        $data['columns_count'] = count($columns);
        return $data;
    }

    /**
     * Expand flexible field: if mode=single, map __single to xs; else remove meta keys.
     */
    public static function expandFlexible(array $value, bool $castNumbers = false): array
    {
        if (isset($value['__mode']) && ($value['__mode'] === 'single')) {
            $single = $value['__single'] ?? null;
            if ($single === '' || $single === null) {
                return [];
            }
            if ($castNumbers && is_numeric($single)) {
                $single = (int) $single;
            }
            return ['xs' => $single];
        }

        unset($value['__mode'], $value['__single']);
        return $value;
    }

    /**
     * Clean a map of values: optionally cast numerics to int and remove empty/null values.
     */
    public static function cleanMap(array $bp, bool $castNumbers = false): array
    {
        $out = [];
        foreach ($bp as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            if ($castNumbers && is_numeric($v)) {
                $v = (int) $v;
            }
            $out[$k] = $v;
        }
        return $out;
    }

    /**
     * Convenience: normalize a flexible field into a per-breakpoint numeric map.
     */
    public static function normalizeFlexibleNumeric(array &$settings, string $fieldName): void
    {
        if (!isset($settings[$fieldName]) || !is_array($settings[$fieldName]))
            return;

        $normalized = self::cleanMap(self::expandFlexible($settings[$fieldName], true), true);

        if ($normalized === []) {
            unset($settings[$fieldName]);
        } else {
            $settings[$fieldName] = $normalized;
        }
    }

    /**
     * Convenience: normalize a flexible field into a per-breakpoint string map.
     */
    public static function normalizeFlexibleString(array &$settings, string $fieldName): void
    {
        if (!isset($settings[$fieldName]) || !is_array($settings[$fieldName]))
            return;

        $map = self::expandFlexible($settings[$fieldName], false);
        $normalized = array_filter($map, fn($v) => $v !== null && $v !== '');

        if ($normalized === []) {
            unset($settings[$fieldName]);
        } else {
            $settings[$fieldName] = $normalized;
        }
    }
}
