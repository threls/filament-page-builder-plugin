<?php

namespace Threls\FilamentPageBuilder\Resources\PageLayoutResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Threls\FilamentPageBuilder\Resources\PageLayoutResource;
use Threls\FilamentPageBuilder\Support\SettingsNormalizer;
use Illuminate\Support\Facades\DB;

class CreatePageLayout extends CreateRecord
{
    protected static string $resource = PageLayoutResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Columns indexing/count
        $data = SettingsNormalizer::assignColumns($data);

        // Centralized normalization to { key: { bp: value } }
        $data['settings'] = SettingsNormalizer::normalizeLayoutSettings($data['settings'] ?? []);

        // Normalize per-column settings
        if (!empty($data['columns']) && is_array($data['columns'])) {
            foreach ($data['columns'] as &$column) {
                $colSettings = SettingsNormalizer::normalizeColumnSettings($column['settings'] ?? []);
                if ($colSettings === []) unset($column['settings']); else $column['settings'] = $colSettings;
            }
            unset($column); // break reference
        }

        return $data;
    }

    public function create(bool $another = false): void
    {
        DB::beginTransaction();
        try {
            parent::create($another);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
