<?php

namespace Threls\FilamentPageBuilder\Resources;

use CactusGalaxy\FilamentAstrotomic\Forms\Components\TranslatableTabs;
use CactusGalaxy\FilamentAstrotomic\Resources\Concerns\ResourceTranslatable;
use CactusGalaxy\FilamentAstrotomic\TranslatableTab;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Hidden;
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
use Threls\FilamentPageBuilder\Enums\PageStatusEnum;
use Threls\FilamentPageBuilder\Models\Page;
use Threls\FilamentPageBuilder\Resources\PageResource\Pages\CreatePage;
use Threls\FilamentPageBuilder\Resources\PageResource\Pages\EditPage;
use Threls\FilamentPageBuilder\Resources\PageResource\Pages\ListPages;
use Threls\FilamentPageBuilder\Resources\PageResource\Support\PageBuilderUtils;
use Threls\FilamentPageBuilder\Resources\PageResource\Support\PageBuilderFormatUtil;
use Threls\FilamentPageBuilder\Resources\PageResource\Support\PageBuilderDehydrateUtil;

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
                                ->addActionLabel('Add a section layout')
                                ->reorderableWithDragAndDrop(false) // TODO: try upgrading to latest filament - issues with dragging at the moment
                                ->reorderableWithButtons()
                                ->formatStateUsing(fn ($state) => PageBuilderFormatUtil::formatBuilderStateForEdit($state, 'Layout'))
                                ->dehydrateStateUsing(fn ($state) => PageBuilderDehydrateUtil::dehydrateBuilderStateForSave($state))
                                ->blockNumbers(false)
                                ->blocks(function () use ($tab) {
                                    // Build available blocks and layouts only once per request for performance.
                                    $availableBlocks = PageBuilderFormatUtil::getAvailableBlocksForTab($tab);
                                    $layouts = PageBuilderUtils::getAllLayoutsWithColumns();
                                    $blocks = [];
                                    foreach ($layouts as $layout) {
                                        $schema = [
                                            // Persist the selected layout id in state (also inferred from type on dehydrate)
                                            Hidden::make('layout_id')->default($layout->id),
                                        ];

                                        foreach ($layout->columns as $i => $col) {
                                            $label = $col->name ?: ('Column ' . ($col->key ?? $col->index));

//                                            dump(['Cols', $col->id, $col]);

                                            $schema[] = Section::make($label)
                                                ->schema([
                                                    // Bind directly to the persisted columns map by column id
                                                    Builder::make('columns.' . $col->id)
                                                        ->label('Components')
                                                        ->hiddenLabel()
                                                        ->dehydrateStateUsing(fn ($state) => PageBuilderDehydrateUtil::dehydrateBuilderStateForSave($state))
                                                        ->blocks($availableBlocks)
                                                        ->blockNumbers(false)
                                                        ->reorderableWithButtons()
                                                        ->reorderableWithDragAndDrop(false)
                                                        ->addActionLabel('Add component in column'),
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








}
