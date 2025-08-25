<?php

namespace Threls\FilamentPageBuilder\Resources;

use CactusGalaxy\FilamentAstrotomic\Forms\Components\TranslatableTabs;
use CactusGalaxy\FilamentAstrotomic\Resources\Concerns\ResourceTranslatable;
use CactusGalaxy\FilamentAstrotomic\TranslatableTab;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Threls\FilamentPageBuilder\Enums\BlueprintFieldTypeEnum;
use Threls\FilamentPageBuilder\Enums\PageStatusEnum;
use Threls\FilamentPageBuilder\Models\BlueprintVersion;
use Threls\FilamentPageBuilder\Models\Page;
use Threls\FilamentPageBuilder\Models\PageLayout;
use Threls\FilamentPageBuilder\Models\RelationshipType;
use Threls\FilamentPageBuilder\Resources\PageResource\Pages\CreatePage;
use Threls\FilamentPageBuilder\Resources\PageResource\Pages\EditPage;
use Threls\FilamentPageBuilder\Resources\PageResource\Pages\ListPages;

class PageResource extends Resource
{
    use ResourceTranslatable;

    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Pages';

    public static function getNavigationGroup(): ?string
    {
        return config('filament-page-builder.navigation_group', 'Content');
    }

    public static function getNavigationIcon(): ?string
    {
        return config('filament-page-builder.navigation_icon', 'heroicon-o-rectangle-stack');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                self::getFormSchema()
            );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (PageStatusEnum $state): string => match ($state) {
                        PageStatusEnum::DRAFT => 'info',
                        PageStatusEnum::PUBLISHED => 'success',
                        PageStatusEnum::ARCHIVED => 'warning',
                    })
                    ->formatStateUsing(fn (PageStatusEnum $state): string => $state->name)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()->color('info'),
                ActionGroup::make([
                    Action::make('publish')
                        ->label(fn (Page $record) => $record->status === PageStatusEnum::PUBLISHED ? 'Unpublish' : 'Publish')
                        ->icon(fn (Page $record) => $record->status === PageStatusEnum::PUBLISHED ? 'heroicon-s-x-mark' : 'heroicon-s-check')
                        ->color(fn (Page $record) => $record->status === PageStatusEnum::PUBLISHED ? 'gray' : 'success')
                        ->visible(fn () => static::canCreate())
                        ->requiresConfirmation()
                        ->action(function (Page $record) {
                            $record->update([
                                'status' => $record->status === PageStatusEnum::PUBLISHED
                                    ? PageStatusEnum::DRAFT
                                    : PageStatusEnum::PUBLISHED,
                            ]);
                        }),
                    Action::make('archive')
                        ->icon('heroicon-s-archive-box-arrow-down')
                        ->color('warning')
                        ->visible(fn (Page $record) => static::canDelete($record))
                        ->requiresConfirmation()
                        ->action(fn (Page $record) => $record->update(['status' => PageStatusEnum::ARCHIVED])),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->action(fn (Page $record) => $record->update(['status' => PageStatusEnum::ARCHIVED])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return config('filament-page-builder.permissions.can_delete', true);
    }

    public static function canCreate(): bool
    {
        return config('filament-page-builder.permissions.can_create', true);
    }

    public static function getFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

            TextInput::make('slug')
                ->required()
                ->readOnly(),

            Select::make('status')
                ->label('Status')
                ->default(PageStatusEnum::DRAFT->value)
                ->options(function (?Page $record) {
                    return collect(PageStatusEnum::cases())
                        ->mapWithKeys(fn ($case) => [
                            $case->value => Str::title($case->name),
                        ])->toArray();
                })
                ->disableOptionWhen(function (?Page $record, string $value): bool {
                    if (! $record || $record->status !== PageStatusEnum::PUBLISHED) {
                        return false;
                    }

                    if ($value === PageStatusEnum::ARCHIVED->value && ! static::canDelete($record)) {
                        return true;
                    }

                    if ($value === PageStatusEnum::DRAFT->value && ! static::canCreate()) {
                        return true;
                    }

                    return false;
                })
                ->required(),

            Section::make('Page builder')
                ->schema([
                    TranslatableTabs::make()
                        ->localeTabSchema(fn (TranslatableTab $tab) => [
                            Builder::make($tab->makeName('content'))
                                ->hiddenLabel()
                                ->generateUuidUsing(false)
                                ->reorderableWithDragAndDrop(true)
                                ->reorderableWithButtons()
                                ->formatStateUsing(fn ($state) => static::formatBuilderStateForEdit($state))
                                ->dehydrateStateUsing(fn ($state) => static::dehydrateBuilderStateForSave($state))
                                ->blockNumbers(false)
                                ->blocks(function () use ($tab) {
                                    // Build available blocks and layouts only once per request for performance.
                                    $availableBlocks = static::getAvailableBlocksForTab($tab);
                                    $layouts = static::getAllLayoutsWithColumns();
                                    $blocks = [];
                                    foreach ($layouts as $layout) {
                                        $schema = [
                                            // Persist the selected layout id in state (also inferred from type on dehydrate)
                                            Hidden::make('layout_id')->default($layout->id),
                                        ];

                                        foreach ($layout->columns as $i => $col) {
                                            $label = $col->name ?: ('Column ' . ($col->key ?? $col->index));

                                            $schema[] = Section::make($label)
                                                ->schema([
                                                    // Bind directly to the persisted columns map by column id
                                                    Builder::make('columns.' . $col->id)
                                                        ->label('Component')
                                                        ->hiddenLabel()
                                                        ->maxItems(1)
                                                        ->formatStateUsing(fn ($state) => static::formatBuilderStateForEdit($state))
                                                        ->dehydrateStateUsing(fn ($state) => static::dehydrateBuilderStateForSave($state))
                                                        ->blocks($availableBlocks)
                                                        ->blockNumbers(false)
                                                        ->reorderable(false),
                                                ]);
                                        }

                                        $blocks[] = Block::make('layout_section:' . $layout->id)
                                            ->label($layout->name)
                                            ->schema($schema);
                                    }
                                    return $blocks;
                                }),
                        ]),
                ]),

        ];
    }

    /**
     * Prepare builder state for the edit UI.
     * - Expands types like `layout_section` to `layout_section:<layout_id>` for clarity.
     * - Normalizes `data.columns` into a map keyed by layout column ID (string).
     * - Expects blueprint components to use per-blueprint edit type `blueprint:<blueprint_id>`.
     */
    protected static function formatBuilderStateForEdit(mixed $state): mixed
    {
        dump(['formatBuilderStateForEdit', $state]);
        if (! is_array($state)) {
            return $state;
        }

        foreach ($state as &$section) {
            if (! is_array($section)) {
                continue;
            }

            $sectionType = $section['type'] ?? null;

            // No catch-all unmatched transformation in Option 1.

            // Handle layout sections
            if ($sectionType === 'layout_section') {
                $layoutId = $section['data']['layout_id'] ?? null;
                if ($layoutId) {
                    // UI convenience: encode layout id in the type while editing
                    $section['type'] = 'layout_section:' . $layoutId;
                }
                // Columns are bound by known layout column IDs in the UI; no normalization needed here.
            }

            // Blueprint components: map canonical type to per-blueprint UI type so Builder matches blocks
            if (
                $sectionType === 'blueprint_component'
                || (is_string($sectionType) && str_starts_with($sectionType, 'blueprint_component:'))
            ) {
                $versionId = $section['data']['blueprint_version_id'] ?? null;
                if (! $versionId && is_string($sectionType) && str_starts_with($sectionType, 'blueprint_component:')) {
                    $parts = explode(':', $sectionType, 2);
                    $versionId = isset($parts[1]) ? (int) $parts[1] : null;
                }
                if ($versionId) {
                    $version = static::getPublishedBlueprintVersions()->firstWhere('id', (int) $versionId)
                        ?? BlueprintVersion::query()->find((int) $versionId);
                    $blueprintId = $version?->blueprint_id;
                    if ($blueprintId) {
                        $section['type'] = 'blueprint:' . (int) $blueprintId;
                    }
                }
            }
        }

        return $state;
    }

    /**
     * Canonicalize builder state before persisting to the database.
     * - Collapses `layout_section:<id>` back to `layout_section` and ensures `data.layout_id` is set.
     * - Persists layout columns as an id-keyed map ordered by the layout's columns.
     * - Normalizes blueprint components to canonical type and data shape.
     */
    protected static function dehydrateBuilderStateForSave(mixed $state): mixed
    {
        dump(['dehydrateBuilderStateForSave', $state]);
        if (! is_array($state)) {
            return $state;
        }

        foreach ($state as &$section) {
            if (! is_array($section)) {
                continue;
            }

            $rawType = $section['type'] ?? '';

            // If this is an unmatched placeholder from edit-time, restore the original type + data first
            if ($rawType === 'unmatched_block') {
                $origType = $section['data']['original_type'] ?? null;
                $origData = $section['data']['original_data'] ?? null;
                if (is_string($origType) && is_array($origData)) {
                    $section['type'] = $origType;
                    $section['data'] = $origData;
                    $rawType = $origType; // fall through to normal canonicalization
                }
            }

            // Layout sections: collapse to canonical shape
            if ($rawType === 'layout_section' || (is_string($rawType) && str_starts_with($rawType, 'layout_section:'))) {
                $section['type'] = 'layout_section';

                $layoutId = $section['data']['layout_id'] ?? null;
                $layoutModel = $layoutId ? static::getLayoutById((int) $layoutId) : null;

                // Persist as id-keyed columns based on layout columns order
                $columnsToPersist = [];
                if ($layoutModel) {
                    $uiColumns = $section['data']['columns'] ?? [];
                    foreach ($layoutModel->columns as $layoutColumn) {
                        $idStr = (string) $layoutColumn->id;
                        $value = $uiColumns[$idStr] ?? [];
                        $columnsToPersist[$idStr] = is_array($value) ? array_values($value) : [];
                    }
                }

                $canonical = [
                    'layout_id' => $layoutId,
                    'columns' => $columnsToPersist,
                ];
                if (isset($section['data']['settings'])) {
                    $canonical['settings'] = $section['data']['settings'];
                }
                $section['data'] = $canonical;
            }

            // Blueprint components: collapse to canonical type + shape (expects 'blueprint:<id>')
            if (is_string($rawType) && str_starts_with($rawType, 'blueprint:')) {
                $section['type'] = 'blueprint_component';

                // Determine version id to persist
                $versionId = null;
                // blueprint:<blueprint_id> -> use latest published version for that blueprint
                $parts = explode(':', $rawType, 2);
                $blueprintId = isset($parts[1]) ? (int) $parts[1] : null;
                if ($blueprintId) {
                    $latest = static::getPublishedBlueprintVersions()
                        ->where('blueprint_id', $blueprintId)
                        ->sortByDesc('version')
                        ->first();
                    $versionId = $latest?->id;
                }

                // Collect fields based on resolved version schema from UI data.* (or nested data.fields as fallback)
                $collected = [];
                if ($versionId) {
                    $version = static::getPublishedBlueprintVersions()->firstWhere('id', (int) $versionId)
                        ?? \Threls\FilamentPageBuilder\Models\BlueprintVersion::query()->find((int) $versionId);
                    $schema = $version?->schema ?? [];
                    $schemaFields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
                    foreach ($schemaFields as $fieldDef) {
                        if (! is_array($fieldDef)) {
                            continue;
                        }
                        $fKey = $fieldDef['key'] ?? null;
                        $fType = (string) ($fieldDef['type'] ?? '');
                        if (! $fKey) {
                            continue;
                        }
                        $val = $section['data'][$fKey] ?? ($section['data']['fields'][$fKey] ?? null);
                        if ($fType === \Threls\FilamentPageBuilder\Enums\BlueprintFieldTypeEnum::GALLERY->value) {
                            if ($val === null || $val === '') {
                                $val = [];
                            } elseif (is_string($val)) {
                                $val = [$val];
                            } elseif (is_array($val)) {
                                $val = array_values($val);
                            }
                        }
                        if ($fType === \Threls\FilamentPageBuilder\Enums\BlueprintFieldTypeEnum::IMAGE->value) {
                            if (is_array($val)) {
                                $val = array_values($val)[0] ?? null;
                            }
                        }
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

    // Dynamic Blueprint fields helpers
    protected static function getBlueprintFieldsSchema(?int $versionId, TranslatableTab $tab): array
    {
        if (! $versionId) {
            return [];
        }

        // Prefer using cached published versions; fallback to a direct find if not present
        $version = static::getPublishedBlueprintVersions()->firstWhere('id', $versionId)
            ?? BlueprintVersion::query()->find($versionId);
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

            $components[] = static::makeBlueprintFieldComponent($key, $type, $field, $tab);
        }

        // Important: bind all blueprint field components under data.fields (relative to block data)
        return [
            Section::make('')
                ->schema($components)
                ->statePath('fields'),
        ];
    }

    protected static function makeBlueprintFieldComponent(string $key, string $type, array $field, TranslatableTab $tab): \Filament\Forms\Components\Component
    {
        $label = $field['label'] ?? $key;
        $help = $field['help'] ?? null;
        $rules = $field['rules'] ?? '';
        $rulesArr = is_array($rules) ? $rules : array_filter(array_map('trim', explode('|', (string) $rules)));
        $options = $field['options'] ?? [];
        // Section for blueprint fields uses statePath('fields'), so bind directly to key
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
                    ->default([])
                    ->afterStateHydrated(function (callable $set, $state) use ($name) {
                        // Normalize to array for single-image to avoid foreach errors on hydration
                        if ($state === null || $state === '') {
                            $set($name, []);
                            return;
                        }
                        if (is_string($state)) {
                            $set($name, [$state]);
                            return;
                        }
                        if (is_array($state)) {
                            $set($name, array_slice(array_values($state), 0, 1));
                            return;
                        }
                        $set($name, []);
                    })
                    ->formatStateUsing(function ($state) {
                        // Ensure single-image state is an array with at most 1 item
                        if ($state === null || $state === '') {
                            return [];
                        }
                        if (is_string($state)) {
                            return [$state];
                        }
                        if (is_array($state)) {
                            return array_slice(array_values($state), 0, 1);
                        }
                        return [];
                    });
                break;
            case BlueprintFieldTypeEnum::GALLERY->value:
                $comp = FileUpload::make($name)
                    ->label($label)
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->directory('page-builder')
                    ->disk(config('filament-page-builder.disk'))
                    ->default([])
                    ->afterStateHydrated(function (callable $set, $state) use ($name) {
                        // Normalize to array on hydration to avoid BaseFileUpload foreach errors
                        if ($state === null || $state === '') {
                            $set($name, []);
                            return;
                        }
                        if (is_string($state)) {
                            $set($name, [$state]);
                            return;
                        }
                        if (is_array($state)) {
                            $set($name, array_values($state));
                            return;
                        }
                        $set($name, []);
                    })
                    ->formatStateUsing(function ($state) {
                        // Ensure multiple file state is an array
                        if ($state === null || $state === '') {
                            return [];
                        }
                        if (is_string($state)) {
                            return [$state];
                        }
                        if (is_array($state)) {
                            return array_values($state);
                        }
                        return [];
                    });
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
                // For blueprint primitive 'relation', present a dropdown of active Relationship Types.
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

    /**
     * Build Builder blocks for each published BlueprintVersion so they appear as first-class components.
     */
    protected static function getBlueprintBlocks(TranslatableTab $tab): array
    {
        $versions = static::getPublishedBlueprintVersions();
        // Group by blueprint and pick latest (highest version)
        $latestByBlueprint = $versions
            ->groupBy('blueprint_id')
            ->map(fn ($group) => $group->sortByDesc('version')->first())
            ->values();

        // Sort by normalized category, then by blueprint name (case-insensitive)
        $sorted = $latestByBlueprint->sortBy(function ($bv) {
            $catKey = static::normalizeCategoryKey($bv->blueprint?->category ?? null);
            $nameKey = strtolower($bv->blueprint?->name ?? '');
            return sprintf('%s|%s', $catKey, $nameKey);
        });

        $blocks = [];
        foreach ($sorted as $bv) {
            $categoryLabel = static::humanizeCategory($bv->blueprint?->category ?? null);
            $name = $bv->blueprint?->name ?? 'Blueprint';
            $label = $categoryLabel . ' · ' . $name;
            $blocks[] = Block::make('blueprint:' . $bv->blueprint_id)
                ->label($label)
                ->schema(static::getBlueprintFieldsSchema($bv->id, $tab));
        }

        return $blocks;
    }

    /**
     * Request-local cache: returns all layouts with columns.
     */
    protected static function getAllLayoutsWithColumns(): ?Collection
    {
        static $cache = null;
        if ($cache === null) {
            $cache = PageLayout::with('columns')->get();
        }
        return $cache;
    }

    /**
     * Request-local cache: lookup a layout by id using the preloaded collection.
     */
    protected static function getLayoutById(int $layoutId): ?\Threls\FilamentPageBuilder\Models\PageLayout
    {
        static $map = null;
        if ($map === null) {
            $map = static::getAllLayoutsWithColumns()->keyBy('id');
        }
        return $map->get($layoutId);
    }

    /**
     * Request-local cache: get the available blocks for the given tab (blueprints only).
     */
    protected static function getAvailableBlocksForTab(TranslatableTab $tab): array
    {
        return static::getBlueprintBlocks($tab);
    }

    /**
     * Request-local cache: get published blueprint versions with blueprint relation.
     */
    protected static function getPublishedBlueprintVersions(): ?Collection
    {
        static $cache = null;
        if ($cache === null) {
            $cache = BlueprintVersion::query()
                ->where('status', 'published')
                ->with('blueprint')
                ->orderBy('blueprint_id')
                ->orderBy('version')
                ->get();
        }
        return $cache;
    }

    // Category helpers
    protected static function normalizeCategoryKey(?string $category): string
    {
        $c = strtolower((string) ($category ?: 'uncategorized'));
        // remove all non-alphanumeric
        return preg_replace('/[^a-z0-9]/', '', $c) ?: 'uncategorized';
    }

    protected static function humanizeCategory(?string $category): string
    {
        $c = trim((string) ($category ?: 'Uncategorized'));
        return ucwords(strtolower($c));
    }
}
