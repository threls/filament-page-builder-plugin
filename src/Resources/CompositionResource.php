<?php

namespace Threls\FilamentPageBuilder\Resources;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Threls\FilamentPageBuilder\Models\Composition;
use Threls\FilamentPageBuilder\Resources\PageResource\Support\PageBuilderDehydrateUtil;
use Threls\FilamentPageBuilder\Resources\PageResource\Support\PageBuilderFormatUtil;
use Threls\FilamentPageBuilder\Resources\PageResource\Support\PageBuilderUtils;
use Threls\FilamentPageBuilder\Resources\CompositionResource\Pages\CreateComposition;
use Threls\FilamentPageBuilder\Resources\CompositionResource\Pages\EditComposition;
use Threls\FilamentPageBuilder\Resources\CompositionResource\Pages\ListCompositions;

class CompositionResource extends Resource
{
    protected static ?string $model = Composition::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'Compositions';

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('filament-page-builder.permissions.can_manage_compositions', true);
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filament-page-builder.navigation_group', 'Content');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Details')
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        Toggle::make('is_active')->default(true)->label('Active'),
                    ])->columns(2),

                Section::make('Structure')
                    ->schema([
                        Builder::make('payload')
                            ->hiddenLabel()
                            ->addActionLabel('Add a layout to composition')
                            ->maxItems(1)
                            ->reorderableWithDragAndDrop(false)
                            ->reorderableWithButtons()
                            ->formatStateUsing(fn ($state) => PageBuilderFormatUtil::formatBuilderStateForEdit($state, 'Composition'))
                            ->dehydrateStateUsing(fn ($state) => PageBuilderDehydrateUtil::dehydrateBuilderStateForSave($state))
                            ->blockNumbers(false)
                            ->blocks(function () {
                                $layouts = PageBuilderUtils::getAllLayoutsWithColumns();

                                $buildLayoutBlocks = function () use (&$buildLayoutBlocks, $layouts) {
                                    $nestedLayoutBlocks = [];
                                    foreach ($layouts as $nestedLayout) {
                                        $nestedSchema = [
                                            Hidden::make('layout_id')->default($nestedLayout->id),
                                        ];
                                        foreach ($nestedLayout->columns as $nestedCol) {
                                            $nestedLabel = $nestedCol->name ?: ('Column ' . ($nestedCol->key ?? $nestedCol->index));
                                            $nestedSchema[] = Section::make($nestedLabel)
                                                ->schema([
                                                    Builder::make('columns.' . $nestedCol->id)
                                                        ->label('Components')
                                                        ->hiddenLabel()
                                                        ->dehydrateStateUsing(fn ($state) => PageBuilderDehydrateUtil::dehydrateBuilderStateForSave($state))
                                                        ->blocks(function () use (&$buildLayoutBlocks) {
                                                            // In compositions, blueprints are placeholders (no fields), and layouts are allowed
                                                            $blueprintBlocks = [];
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
                                                            foreach ($sorted as $bv) {
                                                                $categoryLabel = PageBuilderUtils::humanizeCategory($bv->blueprint?->category ?? null);
                                                                $name = $bv->blueprint?->name ?? 'Blueprint';
                                                                $label = sprintf('%s · %s', $categoryLabel, $name);
                                                                $blueprintBlocks[] = Block::make('blueprint_version:' . $bv->id)
                                                                    ->label($label)
                                                                    ->schema([]); // structure only
                                                            }

                                                            $layoutsPalette = $buildLayoutBlocks();
                                                            return array_merge($layoutsPalette, $blueprintBlocks);
                                                        })
                                                        ->blockNumbers(false)
                                                        ->reorderableWithButtons()
                                                        ->reorderableWithDragAndDrop(false)
                                                        ->addActionLabel('Add item to column'),
                                                ]);
                                        }

                                        $nestedLayoutBlocks[] = Block::make('layout_section:' . $nestedLayout->id)
                                            ->label('Layout · ' . $nestedLayout->name)
                                            ->schema($nestedSchema);
                                    }

                                    return $nestedLayoutBlocks;
                                };

                                // For composition root, only allow adding layout sections
                                $blocks = [];
                                foreach ($layouts as $layout) {
                                    $schema = [
                                        Hidden::make('layout_id')->default($layout->id),
                                    ];
                                    foreach ($layout->columns as $col) {
                                        $label = $col->name ?: ('Column ' . ($col->key ?? $col->index));
                                        $schema[] = Section::make($label)
                                            ->schema([
                                                Builder::make('columns.' . $col->id)
                                                    ->label('Components')
                                                    ->hiddenLabel()
                                                    ->dehydrateStateUsing(fn ($state) => PageBuilderDehydrateUtil::dehydrateBuilderStateForSave($state))
                                                    ->blocks(function () use (&$buildLayoutBlocks) {
                                                        $blueprintBlocks = [];
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
                                                        foreach ($sorted as $bv) {
                                                            $categoryLabel = PageBuilderUtils::humanizeCategory($bv->blueprint?->category ?? null);
                                                            $name = $bv->blueprint?->name ?? 'Blueprint';
                                                            $label = sprintf('%s · %s', $categoryLabel, $name);
                                                            $blueprintBlocks[] = Block::make('blueprint_version:' . $bv->id)
                                                                ->label($label)
                                                                ->schema([]);
                                                        }
                                                        $layoutsPalette = $buildLayoutBlocks();
                                                        return array_merge($layoutsPalette, $blueprintBlocks);
                                                    })
                                                    ->blockNumbers(false)
                                                    ->reorderableWithButtons()
                                                    ->reorderableWithDragAndDrop(false)
                                                    ->addActionLabel('Add item to column'),
                                            ]);
                                    }

                                    $blocks[] = Block::make('layout_section:' . $layout->id)
                                        ->label('Layout · ' . $layout->name)
                                        ->schema($schema);
                                }

                                return $blocks;
                            }),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('updated_at')->dateTime()->since()->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompositions::route('/'),
            'create' => CreateComposition::route('/create'),
            'edit' => EditComposition::route('/{record}/edit'),
        ];
    }
}
