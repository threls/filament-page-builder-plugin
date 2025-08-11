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

        // Settings normalization (handled here so the utility stays generic)
        $settings = $data['settings'] ?? [];

        // Map-based normalization keeps this page in control of field names
        $flexibleNumeric = ['gap-x', 'gap-y'];
        foreach ($flexibleNumeric as $key) {
            SettingsNormalizer::normalizeFlexibleNumeric($settings, $key);
        }

        $flexibleString = ['flex-direction', 'flex-wrap'];
        foreach ($flexibleString as $key) {
            SettingsNormalizer::normalizeFlexibleString($settings, $key);
        }

        // Clean simple scalars (in case some fields come as single values)
        $scalarClean = ['width', 'flex-wrap'];
        foreach ($scalarClean as $key) {
            if (isset($settings[$key]) && ($settings[$key] === null || $settings[$key] === '')) {
                unset($settings[$key]);
            }
        }

        $data['settings'] = $settings;

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
