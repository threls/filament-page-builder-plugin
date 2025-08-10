<?php

namespace Threls\FilamentPageBuilder\Resources;

use CactusGalaxy\FilamentAstrotomic\Forms\Components\TranslatableTabs;
use CactusGalaxy\FilamentAstrotomic\Resources\Concerns\ResourceTranslatable;
use CactusGalaxy\FilamentAstrotomic\TranslatableTab;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Support\Str;
use Threls\FilamentPageBuilder\Enums\PageLayoutTypesEnum;
use Threls\FilamentPageBuilder\Enums\PageGridStyleEnum;
use Threls\FilamentPageBuilder\Enums\PageRelationshipTypeEnum;
use Threls\FilamentPageBuilder\Enums\PageStatusEnum;
use Threls\FilamentPageBuilder\Enums\SectionLayoutTypeEnum;
use Threls\FilamentPageBuilder\Models\Page;
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
                    ->color(fn(PageStatusEnum $state): string => match ($state) {
                        PageStatusEnum::DRAFT => 'info',
                        PageStatusEnum::PUBLISHED => 'success',
                        PageStatusEnum::ARCHIVED => 'warning',
                    })
                    ->formatStateUsing(fn(PageStatusEnum $state): string => $state->name)
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
                        ->label(fn(Page $record) => $record->status === PageStatusEnum::PUBLISHED ? 'Unpublish' : 'Publish')
                        ->icon(fn(Page $record) => $record->status === PageStatusEnum::PUBLISHED ? 'heroicon-s-x-mark' : 'heroicon-s-check')
                        ->color(fn(Page $record) => $record->status === PageStatusEnum::PUBLISHED ? 'gray' : 'success')
                        ->visible(fn() => static::canCreate())
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
                        ->visible(fn(Page $record) => static::canDelete($record))
                        ->requiresConfirmation()
                        ->action(fn(Page $record) => $record->update(['status' => PageStatusEnum::ARCHIVED])),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->action(fn(Page $record) => $record->update(['status' => PageStatusEnum::ARCHIVED])),
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
                ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state))),

            TextInput::make('slug')
                ->required()
                ->disabled()
                ->readOnly(),

            Select::make('status')
                ->label('Status')
                ->default(PageStatusEnum::DRAFT->value)
                ->options(function (?Page $record) {
                    return collect(PageStatusEnum::cases())
                        ->mapWithKeys(fn($case) => [
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
                        ->localeTabSchema(fn(TranslatableTab $tab) => [
                            Builder::make($tab->makeName('content'))
                                ->label('Content')
                                ->generateUuidUsing(false)
                                ->reorderableWithDragAndDrop(false)
                                ->reorderableWithButtons()
                                ->addBetweenAction(fn (\Filament\Forms\Components\Actions\Action $action) => $action->hidden())
                                ->blocks([
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

                                    // Block::make(PageLayoutTypesEnum::IMAGE_CARDS->value)
                                    //     ->schema([
                                    //         TextInput::make('title')->nullable(),
                                    //         Repeater::make('group')->schema([
                                    //             TextInput::make('text'),
                                    //             FileUpload::make('image')
                                    //                 ->directory('page-builder')
                                    //                 ->panelLayout('grid')
                                    //                 ->image()
                                    //                 ->required($tab->isMainLocale())
                                    //                 ->disk(config('filament-page-builder.disk')),
                                    //             TextInput::make('button-text'),
                                    //             TextInput::make('button-path'),
                                    //         ]),
                                    //     ]),

                                    // Block::make(PageLayoutTypesEnum::HORIZONTAL_TICKER->value)
                                    //     ->schema([
                                    //         TextInput::make('title')->nullable(),
                                    //         Repeater::make('group')
                                    //             ->schema([
                                    //                 TextInput::make('title')
                                    //                     ->required($tab->isMainLocale()),
                                    //                 Textarea::make('description')
                                    //                     ->nullable(),
                                    //
                                    //                 FileUpload::make('images')
                                    //                     ->panelLayout('grid')
                                    //                     ->directory('page-builder')
                                    //                     ->multiple()
                                    //                     ->reorderable()
                                    //                     ->image()
                                    //                     ->required($tab->isMainLocale())
                                    //                     ->disk(config('filament-page-builder.disk')),
                                    //             ])
                                    //             ->columns(),
                                    //     ]),

                                    Block::make(PageLayoutTypesEnum::BANNER->value)
                                        ->schema([
                                            TextInput::make('title')
                                                ->nullable(),
                                            TextInput::make('text')
                                                ->nullable(),
                                            RichEditor::make('description')
                                                ->nullable(),
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
                                            TextInput::make('title')
                                                ->required($tab->isMainLocale()),
                                            RichEditor::make('content')
                                                ->required($tab->isMainLocale()),
                                        ]),

                                    Block::make(PageLayoutTypesEnum::IMAGE_AND_RICH_TEXT->value)
                                        ->schema([
                                            Select::make('variant')
                                                ->label('Variant')
                                                ->default(SectionLayoutTypeEnum::IMAGE_LEFT_TEXT_RIGHT->value)
                                                ->options(collect(SectionLayoutTypeEnum::cases())->mapWithKeys(fn($case) => [
                                                    $case->value => $case->name,
                                                ]))->required($tab->isMainLocale()),
                                            TextInput::make('title')
                                                ->nullable(),
                                            FileUpload::make('image')
                                                ->panelLayout('grid')
                                                ->directory('page-builder')
                                                ->image()
                                                ->required($tab->isMainLocale())
                                                ->disk(config('admin.page_builder_disk')),

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
                                            RichEditor::make('content')
                                                ->required(),
                                        ]),


                                    Block::make(PageLayoutTypesEnum::KEY_VALUE_SECTION->value)
                                        ->schema([
                                            Select::make('variant')
                                                ->label('Variant')
                                                ->default(PageGridStyleEnum::NORMAL_GRID->value)
                                                ->options(collect(PageGridStyleEnum::cases())->mapWithKeys(fn($case) => [
                                                    $case->value => $case->name,
                                                ]))->required($tab->isMainLocale()),
                                            TextInput::make('title')
                                                ->nullable(),
                                            Repeater::make('group')
                                                ->schema([
                                                    TextInput::make('title')
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(fn(Set $set, ?string $state) => $set('key', Str::slug($state)))
                                                        ->required($tab->isMainLocale()),
                                                    TextInput::make('key')
                                                        ->readOnly(),
                                                    RichEditor::make('description')
                                                        ->required($tab->isMainLocale()),
                                                    TextInput::make('hint')
                                                        ->nullable(),
                                                    FileUpload::make('image')
                                                        ->panelLayout('grid')
                                                        ->directory('page-builder')
                                                        ->image()
                                                        ->nullable()
                                                        ->disk(config('filament-page-builder.disk')),
                                                ])->columns(),
                                        ]),

                                    // Block::make(PageLayoutTypesEnum::MAP_LOCATION->value)
                                    //     ->schema([
                                    //         TextInput::make('title')
                                    //             ->required($tab->isMainLocale()),
                                    //         TextInput::make('latitude')->required(),
                                    //         TextInput::make('longitude')->required(),
                                    //         TextInput::make('address'),
                                    //     ]),

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
                                        ->schema([
                                        ]),

                                    Block::make(PageLayoutTypesEnum::VIDEO_EMBEDDER->value)
                                        ->schema([
                                            TextInput::make('title')
                                                ->nullable(),
                                            FileUpload::make('video')
                                                ->panelLayout('grid')
                                                ->directory('page-builder')
                                                ->acceptedFileTypes(['video/mp4', 'video/avi', 'video/mpeg', 'video/quicktime'])
                                                ->disk(config('admin.page_builder_disk'))
                                                ->maxSize(20048)
                                                ->nullable()
                                                ->requiredWithout('external_url'),
                                            TextInput::make('external_url')
                                                ->nullable()
                                                ->requiredWithout('video')
                                        ]),
                                ]),
                        ]),

                ]),
        ];
    }
}
