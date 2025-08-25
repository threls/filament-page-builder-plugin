<?php

namespace Threls\FilamentPageBuilder\Resources\PageResource\Support;

use CactusGalaxy\FilamentAstrotomic\TranslatableTab;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Threls\FilamentPageBuilder\Enums\BlueprintFieldTypeEnum;
use Threls\FilamentPageBuilder\Models\RelationshipType;

class PageBuilderFormatUtil
{
    public static function formatBuilderStateForEdit(mixed $state, string $log): mixed
    {
        if (! is_array($state)) {
            return $state;
        }
        foreach ($state as &$section) {
            if (! is_array($section)) {
                continue;
            }

            $sectionType = $section['type'] ?? null;
            if ($sectionType === 'layout_section') {
                $section = self::formatLayoutSectionForEdit($section);
            }
        }
        return $state;
    }

    public static function formatLayoutSectionForEdit(array $section): array
    {
        $layoutId = self::extractLayoutIdForEdit($section);
        if ($layoutId) {
            $section['type'] = 'layout_section:' . $layoutId;
        }

        $columns = self::extractColumnsForEdit($section);
        $columns = self::normalizeBlueprintBlocksInColumns($columns);
        $section['data']['columns'] = $columns;

        return $section;
    }

    public static function extractLayoutIdForEdit(array $section): ?int
    {
        $layoutId = $section['data']['layout_id'] ?? null;
        return $layoutId ? (int) $layoutId : null;
    }

    public static function extractColumnsForEdit(array $section): array
    {
        return is_array($section['data']['columns'] ?? null) ? $section['data']['columns'] : [];
    }

    public static function normalizeBlueprintBlocksInColumns(array $columns): array
    {
        foreach ($columns as $colId => &$blocks) {
            if (! is_array($blocks)) {
                continue;
            }
            foreach ($blocks as &$block) {
                if (! is_array($block)) {
                    continue;
                }
                self::mapBlueprintBlockTypeForEdit($block);
            }
        }
        unset($blocks);
        return $columns;
    }

    public static function mapBlueprintBlockTypeForEdit(array &$block): void
    {
        $bType = $block['type'] ?? null;
        if (
            $bType === 'blueprint_component'
            || (is_string($bType) && str_starts_with($bType, 'blueprint_component:'))
        ) {
            $versionId = $block['data']['blueprint_version_id'] ?? null;
            if (! $versionId && str_starts_with($bType, 'blueprint_component:')) {
                $parts = explode(':', $bType, 2);
                $versionId = isset($parts[1]) ? (int) $parts[1] : null;
            }

            if ($versionId) {
                // Use version-specific block type to ensure old pages keep their versions until edited.
                $block['type'] = 'blueprint_version:' . (int) $versionId;
            }
        }
    }

    // ==== UI schema helpers (Blueprint blocks/fields) ====

    public static function getAvailableBlocksForTab(TranslatableTab $tab): array
    {
        return self::getBlueprintBlocks($tab);
    }

    public static function getBlueprintBlocks(TranslatableTab $tab): array
    {
        // Only list latest published version per blueprint for new additions.
        $versions = PageBuilderUtils::getPublishedBlueprintVersions();
        $latestByBlueprint = $versions
            ->groupBy('blueprint_id')
            ->map(fn ($group) => $group->sortByDesc('version')->first())
            ->values();

        $sorted = $latestByBlueprint->sortBy(function ($bv) {
            $catKey = PageBuilderUtils::normalizeCategoryKey($bv->blueprint?->category ?? null);
            $nameKey = strtolower($bv->blueprint?->name ?? '');
            return sprintf('%s|%s', $catKey, $nameKey);
        });

        $blocks = [];
        foreach ($sorted as $bv) {
            $categoryLabel = PageBuilderUtils::humanizeCategory($bv->blueprint?->category ?? null);
            $name = $bv->blueprint?->name ?? 'Blueprint';
            $label = sprintf('%s · %s', $categoryLabel, $name);
            $blocks[] = Block::make('blueprint_version:' . $bv->id)
                ->label($label)
                ->schema(self::getBlueprintFieldsSchema($bv->id, $tab));
        }

        return $blocks;
    }

    public static function getBlueprintFieldsSchema(?int $versionId, TranslatableTab $tab): array
    {
        if (! $versionId) {
            return [];
        }

        $version = PageBuilderUtils::getBlueprintVersionById($versionId);
        $schema = $version?->schema ?? [];
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];

        $components = [];
        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }
            $key = $field['key'] ?? null;
            $type = (string) ($field['type'] ?? BlueprintFieldTypeEnum::TEXT->value);
            if (! $key) {
                continue;
            }

            $components[] = self::makeBlueprintFieldComponent($key, $type, $field, $tab);
        }

        return [
            Section::make('')
                ->schema($components)
                ->statePath('fields'),
        ];
    }

    public static function makeBlueprintFieldComponent(string $key, string $type, array $field, TranslatableTab $tab): \Filament\Forms\Components\Component
    {
        $label = $field['label'] ?? $key;
        $help = $field['help'] ?? null;
        $rules = $field['rules'] ?? '';
        $rulesArr = is_array($rules) ? $rules : array_filter(array_map('trim', explode('|', (string) $rules)));
        $options = $field['options'] ?? [];
        $name = $key;

        switch ($type) {
            case BlueprintFieldTypeEnum::COLOR->value:
                $comp = \Filament\Forms\Components\ColorPicker::make($name)->label($label);
                break;
            case BlueprintFieldTypeEnum::DATE->value:
                $comp = \Filament\Forms\Components\DatePicker::make($name)->label($label);
                break;
            case BlueprintFieldTypeEnum::DATETIME->value:
            case BlueprintFieldTypeEnum::DATETIME_LOCAL->value:
                $comp = \Filament\Forms\Components\DateTimePicker::make($name)->label($label);
                break;
            case BlueprintFieldTypeEnum::TIME->value:
                $comp = \Filament\Forms\Components\TimePicker::make($name)->label($label);
                break;
            case BlueprintFieldTypeEnum::EMAIL->value:
                $comp = TextInput::make($name)->label($label)->email();
                break;
            case BlueprintFieldTypeEnum::URL->value:
                $comp = TextInput::make($name)->label($label)->url();
                break;
            case BlueprintFieldTypeEnum::TEXTAREA->value:
                $comp = Textarea::make($name)->label($label)->rows(4);
                break;
            case BlueprintFieldTypeEnum::RICH_TEXT->value:
                $comp = RichEditor::make($name)->label($label);
                break;
            case BlueprintFieldTypeEnum::IMAGE->value:
                $comp = FileUpload::make($name)
                    ->label($label)
                    ->image()
                    ->multiple()
                    ->maxFiles(1)
                    ->directory('page-builder')
                    ->disk(config('filament-page-builder.disk'))
                    ->default([]);
                break;
            case BlueprintFieldTypeEnum::GALLERY->value:
                $comp = FileUpload::make($name)
                    ->label($label)
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->directory('page-builder')
                    ->disk(config('filament-page-builder.disk'))
                    ->default([]);
                break;
            case BlueprintFieldTypeEnum::NUMBER->value:
                $comp = TextInput::make($name)->label($label)->numeric();
                break;
            case BlueprintFieldTypeEnum::TOGGLE->value:
                $comp = Toggle::make($name)->label($label);
                break;
            case BlueprintFieldTypeEnum::SELECT->value:
                $choices = $options['choices'] ?? [];
                $comp = Select::make($name)
                    ->label($label)
                    ->options(is_array($choices) ? $choices : [])
                    ->searchable();
                if (! empty($options['multiple'])) {
                    $comp = $comp->multiple();
                }
                break;
            case BlueprintFieldTypeEnum::RELATION->value:
                $all = RelationshipType::query()
                    ->where('is_active', true)
                    ->pluck('name', 'handle')
                    ->toArray();

                $allowed = $options['allowed_relationship_type_handles'] ?? null;
                if (is_array($allowed) && ! empty($allowed)) {
                    $all = array_intersect_key($all, array_flip($allowed));
                }

                $comp = Select::make($name)
                    ->label($label)
                    ->options($all)
                    ->searchable()
                    ->preload();

                $defaultHandle = $options['relationship_type_handle'] ?? null;
                if ($defaultHandle && isset($all[$defaultHandle])) {
                    $comp = $comp->default($defaultHandle);
                }
                break;
            case BlueprintFieldTypeEnum::TEXT->value:
            default:
                $comp = TextInput::make($name)->label($label);
                break;
        }

        if ($help) {
            $comp = $comp->helperText($help);
        }
        if (! empty($options['placeholder']) && method_exists($comp, 'placeholder')) {
            $comp = $comp->placeholder($options['placeholder']);
        }
        if (! empty($rulesArr) && method_exists($comp, 'rules')) {
            $comp = $comp->rules($rulesArr);
        }
        if (in_array('required', $rulesArr, true)) {
            $comp = $comp->required();
        }

        return $comp;
    }
}
