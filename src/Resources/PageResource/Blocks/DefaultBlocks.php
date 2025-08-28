<?php

namespace Threls\FilamentPageBuilder\Resources\PageResource\Blocks;

use CactusGalaxy\FilamentAstrotomic\TranslatableTab;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Threls\FilamentPageBuilder\Enums\PageGridStyleEnum;
use Threls\FilamentPageBuilder\Enums\PageLayoutTypesEnum;
use Threls\FilamentPageBuilder\Enums\PageRelationshipTypeEnum;
use Threls\FilamentPageBuilder\Enums\SectionLayoutTypeEnum;

// TODO: Gary - remove this class
class DefaultBlocks
{
    /**
     * Return the core builder blocks used across page layouts.
     */
    public static function build(TranslatableTab $tab): array
    {
        return [
            Block::make(PageLayoutTypesEnum::HERO_SECTION->value)
                ->schema([
                    TextInput::make('title')
                        ->required($tab->isMainLocale()),
                    TextInput::make('subtitle'),
                    FileUpload::make('image')
                        ->panelLayout('grid')
                        ->directory('page-builder')
                        ->image()
                        ->nullable()
                        ->disk(config('filament-page-builder.disk')),
                    FileUpload::make('sticker')
                        ->panelLayout('grid')
                        ->directory('page-builder')
                        ->image()
                        ->nullable()
                        ->disk(config('filament-page-builder.disk')),
                    TextInput::make('button-text'),
                    TextInput::make('button-path'),
                ])
                ->columns(),

            Block::make(PageLayoutTypesEnum::IMAGE_GALLERY->value)
                ->schema([
                    TextInput::make('text'),
                    FileUpload::make('images')
                        ->panelLayout('grid')
                        ->directory('page-builder')
                        ->multiple()
                        ->reorderable()
                        ->image()
                        ->required($tab->isMainLocale())
                        ->disk(config('filament-page-builder.disk')),
                    TextInput::make('button-text'),
                    TextInput::make('button-path'),
                ]),

            Block::make(PageLayoutTypesEnum::BANNER->value)
                ->schema([
                    TextInput::make('title')->nullable(),
                    TextInput::make('text')->nullable(),
                    RichEditor::make('description')->nullable(),
                    FileUpload::make('background-image')
                        ->panelLayout('grid')
                        ->directory('page-builder')
                        ->image()
                        ->nullable()
                        ->disk(config('filament-page-builder.disk')),
                    FileUpload::make('image')
                        ->panelLayout('grid')
                        ->directory('page-builder')
                        ->image()
                        ->nullable()
                        ->disk(config('filament-page-builder.disk')),
                    TextInput::make('button-text'),
                    TextInput::make('button-path'),
                ]),

            Block::make(PageLayoutTypesEnum::RICH_TEXT_PAGE->value)
                ->schema([
                    TextInput::make('title')->required($tab->isMainLocale()),
                    RichEditor::make('content')->required($tab->isMainLocale()),
                ]),

            Block::make(PageLayoutTypesEnum::IMAGE_AND_RICH_TEXT->value)
                ->schema([
                    Select::make('variant')
                        ->label('Variant')
                        ->default(SectionLayoutTypeEnum::IMAGE_LEFT_TEXT_RIGHT->value)
                        ->options(collect(SectionLayoutTypeEnum::cases())->mapWithKeys(fn($case) => [
                            $case->value => $case->name,
                        ]))->required($tab->isMainLocale()),
                    TextInput::make('title')->nullable(),
                    FileUpload::make('image')
                        ->panelLayout('grid')
                        ->directory('page-builder')
                        ->image()
                        ->required($tab->isMainLocale())
                        ->disk(config('filament-page-builder.disk')),
                    FileUpload::make('sticker')
                        ->panelLayout('grid')
                        ->directory('page-builder')
                        ->image()
                        ->nullable()
                        ->disk(config('filament-page-builder.disk')),
                    FileUpload::make('background-image')
                        ->panelLayout('grid')
                        ->directory('page-builder')
                        ->image()
                        ->nullable()
                        ->disk(config('filament-page-builder.disk')),
                    RichEditor::make('content')->required(),
                ]),

            Block::make(PageLayoutTypesEnum::KEY_VALUE_SECTION->value)
                ->schema([
                    Select::make('variant')
                        ->label('Variant')
                        ->default(PageGridStyleEnum::NORMAL_GRID->value)
                        ->options(collect(PageGridStyleEnum::cases())->mapWithKeys(fn($case) => [
                            $case->value => $case->name,
                        ]))->required($tab->isMainLocale()),
                    TextInput::make('title')->nullable(),
                    Repeater::make('group')
                        ->schema([
                            TextInput::make('title')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn(Set $set, ?string $state) => $set('key', Str::slug($state)))
                                ->required($tab->isMainLocale()),
                            TextInput::make('key')->readOnly(),
                            RichEditor::make('description')->required($tab->isMainLocale()),
                            TextInput::make('hint')->nullable(),
                            FileUpload::make('image')
                                ->panelLayout('grid')
                                ->directory('page-builder')
                                ->image()
                                ->nullable()
                                ->disk(config('filament-page-builder.disk')),
                        ])->columns(),
                ]),

            Block::make(PageLayoutTypesEnum::RELATIONSHIP_CONTENT->value)
                ->schema([
                    Select::make('relationship')
                        ->options(collect(PageRelationshipTypeEnum::cases())->mapWithKeys(fn($case) => [
                            $case->value => $case->name,
                        ]))
                        ->required($tab->isMainLocale())
                        ->searchable(),
                ]),

            Block::make(PageLayoutTypesEnum::DIVIDER->value)
                ->schema([]),

            Block::make(PageLayoutTypesEnum::VIDEO_EMBEDDER->value)
                ->schema([
                    TextInput::make('title')->nullable(),
                    FileUpload::make('video')
                        ->panelLayout('grid')
                        ->directory('page-builder')
                        ->acceptedFileTypes(['video/mp4', 'video/avi', 'video/mpeg', 'video/quicktime'])
                        ->disk(config('filament-page-builder.disk'))
                        ->maxSize(20048)
                        ->nullable()
                        ->requiredWithout('external_url'),
                    TextInput::make('external_url')->nullable()->requiredWithout('video'),
                ]),
        ];
    }
}
