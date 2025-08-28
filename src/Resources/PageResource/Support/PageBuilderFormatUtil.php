<?php

namespace Threls\FilamentPageBuilder\Resources\PageResource\Support;

use CactusGalaxy\FilamentAstrotomic\TranslatableTab;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Threls\FilamentPageBuilder\Enums\BlueprintFieldTypeEnum;
use Illuminate\Support\Str;
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
            } elseif ($sectionType === 'composition') {
                // Convert to editor block type: composition:<id>
                $compId = (int) ($section['data']['composition_id'] ?? 0);
                if ($compId > 0) {
                    $section['type'] = 'composition:' . $compId;
                }
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
        // Normalize all blocks in columns, including nested layout sections and blueprint components.
        $columns = self::normalizeBlocksInColumns($columns);
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

    public static function normalizeBlocksInColumns(array $columns): array
    {
        foreach ($columns as $colId => &$blocks) {
            if (! is_array($blocks)) {
                continue;
            }
            foreach ($blocks as &$block) {
                if (! is_array($block)) {
                    continue;
                }
                // Convert nested layout sections to editor-friendly types and normalize their inner columns.
                if (($block['type'] ?? null) === 'layout_section') {
                    $block = self::formatLayoutSectionForEdit($block);
                    continue;
                }
                // Normalize blueprint component version mapping.
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

            // Ensure a stable instance id is present during edit by copying persisted instance_key
            $instanceKey = $block['data']['instance_key'] ?? null;
            if ($instanceKey && empty($block['data']['instance_id'])) {
                $block['data']['instance_id'] = $instanceKey;
            }
        }
    }

    // ==== UI schema helpers (Blueprint blocks/fields) ====

    public static function getAvailableBlueprintBlocks(): array
    {
        return self::getBlueprintBlocks();
    }

    /**
     * Build composition blocks for use at the root of PageResource.
     * Each composition is a locked structure; only blueprint fields inside are editable.
     */
    public static function getCompositionBlocks(): array
    {
        $compositions = PageBuilderUtils::getActiveCompositions();
        if (! $compositions || $compositions->isEmpty()) {
            return [];
        }

        $blocks = [];
        foreach ($compositions as $comp) {
            $label = 'Composition · ' . $comp->name;
            $schema = self::buildCompositionFieldsSchema($comp->payload ?? []);
            // Prepend hidden id and ensure fields are stored under data.fields
            array_unshift($schema, Hidden::make('composition_id')->default($comp->id));
            $blocks[] = Block::make('composition:' . $comp->id)
                ->label($label)
                ->schema([
                    Group::make()
                        ->schema($schema)
                        ->statePath('fields'),
                ]);
        }

        return $blocks;
    }

    /**
     * Build a static schema from a composition payload, flattening inner blueprint fields into sections.
     */
    public static function buildCompositionFieldsSchema(array $payload): array
    {
        // Build nested components from nodes, returning components instead of mutating outer state
        $buildFromNodes = function (array $nodes, bool $isNested = false) use (&$buildFromNodes) {
            $out = [];

            $processedRootLayout = false;
            foreach ($nodes as $node) {
                if (! is_array($node)) {
                    continue;
                }

                $rawType = (string) ($node['type'] ?? '');

                // Handle layout sections (both canonical and editor-style types)
                if ($rawType === 'layout_section' || str_starts_with($rawType, 'layout_section:')) {
                    // At the root level of a composition, enforce a single root layout
                    if (! $isNested && $processedRootLayout) {
                        continue;
                    }
                    $layoutId = null;
                    if (str_starts_with($rawType, 'layout_section:')) {
                        $parts = explode(':', $rawType, 2);
                        $layoutId = isset($parts[1]) ? (int) $parts[1] : null;
                    }
                    if (! $layoutId) {
                        $layoutId = (int) ($node['data']['layout_id'] ?? 0);
                    }

                    $layout = $layoutId ? PageBuilderUtils::getLayoutById($layoutId) : null;
                    $layoutLabel = $layout ? ('Layout · ' . $layout->name) : 'Layout';
                    $columns = is_array(($node['data']['columns'] ?? null)) ? $node['data']['columns'] : [];

                    $layoutSections = [];
                    foreach ($columns as $colId => $blocks) {
                        $col = $layout?->columns->firstWhere('id', (int) $colId);
                        $colLabel = $col?->name ?: ('Column ' . ($col?->key ?? $col?->index ?? $colId));

                        $sectionItems = [];
                        $bpCounter = 0; // unique per-column index for blueprint instances
                        foreach ((array) $blocks as $blk) {
                            if (! is_array($blk)) {
                                continue;
                            }

                            $bType = (string) ($blk['type'] ?? '');

                            // Blueprint components (canonical and editor-style types)
                            if ($bType === 'blueprint_component' || str_starts_with($bType, 'blueprint_version:')) {
                                $versionId = null;
                                if (str_starts_with($bType, 'blueprint_version:')) {
                                    $parts = explode(':', $bType, 2);
                                    $versionId = isset($parts[1]) ? (int) $parts[1] : null;
                                }
                                if (! $versionId) {
                                    $versionId = (int) ($blk['data']['blueprint_version_id'] ?? 0);
                                }

                                if ($versionId) {
                                    $fieldsSchema = self::getBlueprintFieldsSchema($versionId);
                                    // Unwrap inner components from Section(statePath('fields'))
                                    $inner = $fieldsSchema[0]->getChildComponents();
                                    // Namespace each blueprint instance using a stable instance key if available
                                    $instanceKey = (string) ($blk['data']['instance_key'] ?? ($blk['data']['instance_id'] ?? ''));
                                    if ($instanceKey === '') {
                                        // Legacy payload without instance key: fall back to per-column counter
                                        $bpCounter++;
                                        $instanceKey = (string) $bpCounter;
                                    }
                                    $sectionItems[] = Group::make()
                                        ->statePath(sprintf('blueprints.%s.%s', $colId, $instanceKey))
                                        ->schema($inner);
                                }
                                continue;
                            }

                            // Nested layout sections: build and embed under current column
                            if ($bType === 'layout_section' || str_starts_with($bType, 'layout_section:')) {
                                $nested = $buildFromNodes([$blk], true);
                                if (! empty($nested)) {
                                    // $nested already returns one or more Section components representing layouts
                                    foreach ($nested as $nestedComp) {
                                        $sectionItems[] = $nestedComp;
                                    }
                                }
                            }
                        }

                        if (! empty($sectionItems)) {
                            $layoutSections[] = Section::make($colLabel)
                                ->schema($sectionItems);
                        }
                    }
                    if (! empty($layoutSections)) {
                        if ($isNested) {
                            // For nested layouts, keep the layout wrapper section for clarity
                            $out[] = Section::make($layoutLabel)
                                ->schema($layoutSections);
                        } else {
                            // At the root level, flatten: return only the column sections to save space
                            foreach ($layoutSections as $sectionComp) {
                                $out[] = $sectionComp;
                            }
                        }
                    }

                    if (! $isNested) {
                        $processedRootLayout = true;
                    }
                }
            }

            return $out;
        };

        return $buildFromNodes($payload, false);
    }

    public static function getBlueprintBlocks(): array
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
            $fields = self::getBlueprintFieldsSchema($bv->id);
            // Include a stable hidden instance id at the block root so it persists across reorders
            array_unshift($fields, Hidden::make('instance_id')->default(fn () => (string) Str::uuid()));
            $blocks[] = Block::make('blueprint_version:' . $bv->id)
                ->label($label)
                ->schema($fields);
        }

        return $blocks;
    }

    public static function getBlueprintFieldsSchema(?int $versionId): array
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

            $components[] = self::makeBlueprintFieldComponent($key, $type, $field);
        }

        return [
            Section::make('')
                ->schema($components)
                ->statePath('fields'),
        ];
    }

    public static function makeBlueprintFieldComponent(string $key, string $type, array $field): \Filament\Forms\Components\Component
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
