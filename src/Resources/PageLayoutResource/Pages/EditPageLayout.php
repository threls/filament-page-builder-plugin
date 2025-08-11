<?php

namespace Threls\FilamentPageBuilder\Resources\PageLayoutResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Threls\FilamentPageBuilder\Resources\PageLayoutResource;
use Threls\FilamentPageBuilder\Support\SettingsNormalizer;
use Illuminate\Support\Facades\DB;

class EditPageLayout extends EditRecord
{
    protected static string $resource = PageLayoutResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Columns indexing/count
        $data = SettingsNormalizer::assignColumns($data);

        // Settings normalization (handled here so the utility stays generic)
        $settings = $data['settings'] ?? [];

        SettingsNormalizer::normalizeFlexibleNumeric($settings, 'gap-x');

        SettingsNormalizer::normalizeFlexibleNumeric($settings, 'gap-y');

        SettingsNormalizer::normalizeFlexibleString($settings, 'flex-direction');

        SettingsNormalizer::normalizeFlexibleString($settings, 'flex-wrap');

        // Clean simple scalars
        if (isset($settings['flex-wrap']) && ($settings['flex-wrap'] === null || $settings['flex-wrap'] === '')) {
            unset($settings['flex-wrap']);
        }
        if (isset($settings['width']) && ($settings['width'] === null || $settings['width'] === '')) {
            unset($settings['width']);
        }

        $data['settings'] = $settings;

        // Normalize per-column settings
        if (!empty($data['columns']) && is_array($data['columns'])) {
            foreach ($data['columns'] as &$column) {
                $colSettings = $column['settings'] ?? [];
                SettingsNormalizer::normalizeFlexibleNumeric($colSettings, 'weight');
                if ($colSettings === []) {
                    unset($column['settings']);
                } else {
                    $column['settings'] = $colSettings;
                }
            }
            unset($column); // break reference
        }

        return $data;
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSaveNotification = true): void
    {
        DB::beginTransaction();
        try {
            parent::save($shouldRedirect, $shouldSendSaveNotification);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
