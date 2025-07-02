<?php

namespace Threls\FilamentPageBuilder\Resources;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Threls\FilamentPageBuilder\Enums\PageLayoutTypesEnum;
use Threls\FilamentPageBuilder\Enums\PageRelationshipTypeEnum;
use Threls\FilamentPageBuilder\Enums\PageStatusEnum;
use Threls\FilamentPageBuilder\Models\Page;
use Threls\FilamentPageBuilder\Resources\PageResource\Pages\CreatePage;
use Threls\FilamentPageBuilder\Resources\PageResource\Pages\EditPage;
use Threls\FilamentPageBuilder\Resources\PageResource\Pages\ListPages;

class PageResource extends Resource
{
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
                Action::make('publish')
                    ->icon('heroicon-s-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Page $record) => $record->update(['status' => PageStatusEnum::PUBLISHED])),
                Action::make('archive')
                    ->icon('heroicon-s-archive-box-arrow-down')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Page $record) => $record->update(['status' => PageStatusEnum::ARCHIVED])),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): EloquentBuilder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getFormSchema(): array
    {
        $locales = config('filament-page-builder.languages', ['en' => 'English']);

        return [
            Tabs::make('Translations')
                ->tabs(
                    collect($locales)->map(function ($label, $locale) {
                        return Tabs\Tab::make($label)
                            ->schema([
                                TextInput::make("title.{$locale}")
                                    ->label('Title')
                                    ->required($locale === config('app.locale', 'en'))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state) use ($locale) {
                                        if ($locale === config('app.locale', 'en')) {
                                            $set("slug.{$locale}", Str::slug($state));
                                        }
                                    }),

                                TextInput::make("slug.{$locale}")
                                    ->label('Slug')
                                    ->required($locale === config('app.locale', 'en')),
                            ]);
                    })->toArray()
                ),

            Select::make('status')
                ->label('Status')
                ->default(PageStatusEnum::DRAFT->value)
                ->options(collect(PageStatusEnum::cases())->mapWithKeys(fn ($case) => [
                    $case->value => $case->name,
                ]))->required(),

            Section::make('Page builder')
                ->schema([
                    Tabs::make('Content Translations')
                        ->tabs(
                            collect($locales)->map(function ($label, $locale) {
                                return Tabs\Tab::make($label)
                                    ->schema([
                                        Builder::make("content.{$locale}")
                                            ->label('Content')
                                            ->blocks([
                                                Block::make(PageLayoutTypesEnum::HERO_SECTION->value)
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->required(),
                                                        TextInput::make('subtitle'),

                                                        FileUpload::make('image')
                                                            ->panelLayout('grid')
                                                            ->directory('page-builder')
                                                            ->image()
                                                            ->required()
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
                                                            ->required()
                                                            ->disk(config('filament-page-builder.disk')),

                                                        TextInput::make('button-text'),
                                                        TextInput::make('button-path'),
                                                    ]),

                                                Block::make(PageLayoutTypesEnum::IMAGE_CARDS->value)
                                                    ->schema([
                                                        Repeater::make('group')->schema([
                                                            TextInput::make('text'),
                                                            FileUpload::make('image')
                                                                ->directory('page-builder')
                                                                ->panelLayout('grid')
                                                                ->image()
                                                                ->required()
                                                                ->disk(config('filament-page-builder.disk')),
                                                            TextInput::make('button-text'),
                                                            TextInput::make('button-path'),
                                                        ]),
                                                    ]),

                                                Block::make(PageLayoutTypesEnum::HORIZONTAL_TICKER->value)
                                                    ->schema([
                                                        TextInput::make('title')->nullable(),
                                                        Repeater::make('group')
                                                            ->schema([
                                                                TextInput::make('title')
                                                                    ->required(),
                                                                Textarea::make('description')
                                                                    ->nullable(),

                                                                FileUpload::make('images')
                                                                    ->panelLayout('grid')
                                                                    ->directory('page-builder')
                                                                    ->multiple()
                                                                    ->reorderable()
                                                                    ->image()
                                                                    ->required()
                                                                    ->disk(config('filament-page-builder.disk')),
                                                            ])
                                                            ->columns(),
                                                    ]),

                                                Block::make(PageLayoutTypesEnum::BANNER->value)
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->required(),
                                                        TextInput::make('text')
                                                            ->required(),
                                                        FileUpload::make('image')
                                                            ->panelLayout('grid')
                                                            ->directory('page-builder')
                                                            ->reorderable()
                                                            ->image()
                                                            ->disk(config('filament-page-builder.disk')),

                                                        TextInput::make('button-text'),
                                                        TextInput::make('button-path'),
                                                    ]),

                                                Block::make(PageLayoutTypesEnum::RICH_TEXT_PAGE->value)
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->required(),
                                                        RichEditor::make('content')
                                                            ->required(),
                                                    ]),

                                                Block::make(PageLayoutTypesEnum::KEY_VALUE_SECTION->value)
                                                    ->schema([
                                                        Repeater::make('group')
                                                            ->schema([
                                                                TextInput::make('title')
                                                                    ->live(onBlur: true)
                                                                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('key', Str::slug($state)))
                                                                    ->required(),
                                                                TextInput::make('key')
                                                                    ->readOnly(),
                                                                RichEditor::make('description')
                                                                    ->required(),
                                                                TextInput::make('hint')
                                                                    ->nullable(),
                                                            ])->columns(),
                                                    ]),

                                                Block::make(PageLayoutTypesEnum::MAP_LOCATION->value)
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->required(),
                                                        TextInput::make('latitude')->required(),
                                                        TextInput::make('longitude')->required(),
                                                        TextInput::make('address'),
                                                    ]),

                                                Block::make(PageLayoutTypesEnum::RELATIONSHIP_CONTENT->value)
                                                    ->schema([
                                                        Select::make('relationship')
                                                            ->options(collect(PageRelationshipTypeEnum::cases())->mapWithKeys(fn ($case) => [
                                                                $case->value => $case->name,
                                                            ]))
                                                            ->required()
                                                            ->searchable(),
                                                    ]),
                                            ]),
                                    ]);
                            })->toArray()
                        ),
                ]),
        ];
    }
}
