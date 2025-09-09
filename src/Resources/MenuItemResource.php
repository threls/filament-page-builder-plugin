<?php

namespace Threls\FilamentPageBuilder\Resources;

use CactusGalaxy\FilamentAstrotomic\Resources\Concerns\ResourceTranslatable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Threls\FilamentPageBuilder\Models\MenuItem;
use Threls\FilamentPageBuilder\Models\Page;
use Threls\FilamentPageBuilder\Resources\MenuItemResource\Pages;

class MenuItemResource extends Resource
{
    use ResourceTranslatable;

    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationParentItem = 'Menus';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Hide from main navigation, access through Menu resource
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                self::getFormSchema()
            );
    }

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Basic Information')
                ->schema([
                    Forms\Components\Select::make('menu_id')
                        ->label('Menu')
                        ->relationship('menu', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->columnSpan(1),

                    Forms\Components\Select::make('type')
                        ->label('Link Type')
                        ->options([
                            'parent' => 'Parent (No Link)',
                            'page' => 'Page',
                            'internal' => 'Internal Link',
                            'external' => 'External Link',
                        ])
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn (Set $set) => $set('page_id', null))
                        ->columnSpan(1),

                    Forms\Components\Select::make('page_id')
                        ->label('Page')
                        ->options(Page::query()->pluck('title', 'id'))
                        ->searchable()
                        ->visible(fn (Get $get): bool => $get('type') === 'page')
                        ->required(fn (Get $get): bool => $get('type') === 'page')
                        ->columnSpan(1),

                    Forms\Components\Select::make('parent_id')
                        ->label('Parent Item')
                        ->options(function (Get $get, ?MenuItem $record) {
                            $menuId = $get('menu_id');
                            if (! $menuId) {
                                return [];
                            }

                            return MenuItem::query()
                                ->where('menu_id', $menuId)
                                ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                ->whereNull('parent_id')
                                ->with('translations')
                                ->get()
                                ->mapWithKeys(function ($item) {
                                    return [$item->id => $item->name ?? 'Untitled'];
                                });
                        })
                        ->searchable()
                        ->columnSpan(1),

                    Forms\Components\Select::make('target')
                        ->label('Open In')
                        ->options([
                            '_self' => 'Same Window',
                            '_blank' => 'New Window',
                        ])
                        ->default('_self')
                        ->visible(fn (Get $get): bool => $get('type') !== 'parent')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('order')
                        ->label('Order')
                        ->numeric()
                        ->default(1)
                        ->columnSpan(1),

                    Forms\Components\Toggle::make('is_visible')
                        ->label('Visible')
                        ->default(true)
                        ->columnSpan(1),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\FileUpload::make('icon')
                                ->label('Icon')
                                ->image()
                                ->maxSize(1024)
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('1:1')
                                ->imageResizeTargetWidth('64')
                                ->imageResizeTargetHeight('64')
                                ->disk('public')
                                ->directory('menu-icons'),

                            Forms\Components\FileUpload::make('icon_alt')
                                ->label('Alternative Icon (Hover/Active)')
                                ->image()
                                ->maxSize(1024)
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('1:1')
                                ->imageResizeTargetWidth('64')
                                ->imageResizeTargetHeight('64')
                                ->disk('public')
                                ->directory('menu-icons'),
                        ])
                        ->columnSpan('full'),
                ])
                ->columns(3),

            Forms\Components\Section::make('Content')
                ->schema([
                    Forms\Components\Tabs::make('Translations')
                        ->tabs(
                            collect(config('translatable.locales', ['en']))
                                ->map(function ($locale) {
                                    $isDefault = $locale === config('translatable.fallback_locale', 'en');
                                    $label = strtoupper($locale);

                                    return Forms\Components\Tabs\Tab::make($locale)
                                        ->label($label)
                                        ->schema([
                                            Forms\Components\TextInput::make("{$locale}.name")
                                                ->label('Name')
                                                ->required($isDefault)
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make("{$locale}.url")
                                                ->label('URL')
                                                ->maxLength(500),
                                        ]);
                                })
                                ->toArray()
                        ),
                ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('menu_id')
            ->defaultSort('parent_id')
            ->defaultSort('order')
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('menu.name')
                    ->label('Menu')
                    ->searchable()
                    ->sortable()
                    ->width(150),

                Tables\Columns\ImageColumn::make('icon')
                    ->label('Icon')
                    ->disk('public')
                    ->width(30)
                    ->height(30)
                    ->circular()
                    ->width(60),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(function (MenuItem $record): string {
                        $depth = $record->getDepth();
                        $prefix = str_repeat('— ', $depth);

                        return $prefix . ($record->name ?? 'Untitled');
                    })
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg')
                    ->extraAttributes(['class' => 'min-w-0'])
                    ->grow(true),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'parent' => 'gray',
                        'page' => 'info',
                        'internal' => 'success',
                        'external' => 'warning',
                        default => 'gray',
                    })
                    ->width(100),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->getStateUsing(fn (MenuItem $record): ?string => $record->parent?->name)
                    ->searchable()
                    ->toggleable()
                    ->width(150),

                Tables\Columns\TextColumn::make('order')
                    ->label('Order')
                    ->sortable()
                    ->width(80),

                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean()
                    ->width(80),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('menu_id')
                    ->label('Menu')
                    ->relationship('menu', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'parent' => 'Parent',
                        'page' => 'Page',
                        'internal' => 'Internal Link',
                        'external' => 'External Link',
                    ]),

                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visibility'),
            ])
            ->actions([
                Tables\Actions\Action::make('indent')
                    ->label('')
                    ->icon('heroicon-m-chevron-right')
                    ->iconButton()
                    ->size('sm')
                    ->color('gray')
                    ->tooltip('Indent (Make Sub-item)')
                    ->action(function (MenuItem $record): void {
                        $previousSibling = MenuItem::where('menu_id', $record->menu_id)
                            ->where('parent_id', $record->parent_id)
                            ->where('order', '<', $record->order)
                            ->orderBy('order', 'desc')
                            ->first();

                        if ($previousSibling && $record->getDepth() < ($record->menu->max_depth - 1)) {
                            $newOrder = MenuItem::where('parent_id', $previousSibling->id)->max('order') + 1;
                            $record->update([
                                'parent_id' => $previousSibling->id,
                                'order' => $newOrder ?? 1,
                            ]);
                            static::reorderSiblings($record->menu_id, $previousSibling->parent_id);
                        }
                    })
                    ->visible(function (MenuItem $record): bool {
                        $previousSibling = MenuItem::where('menu_id', $record->menu_id)
                            ->where('parent_id', $record->parent_id)
                            ->where('order', '<', $record->order)
                            ->orderBy('order', 'desc')
                            ->first();

                        if (! $previousSibling) {
                            return false;
                        }

                        return $record->getDepth() < ($record->menu->max_depth - 1);
                    }),
                Tables\Actions\Action::make('unindent')
                    ->label('')
                    ->icon('heroicon-m-chevron-left')
                    ->iconButton()
                    ->size('sm')
                    ->color('gray')
                    ->tooltip('Unindent (Move to Parent Level)')
                    ->action(function (MenuItem $record): void {
                        if ($record->parent_id) {
                            $parent = $record->parent;
                            $oldParentId = $record->parent_id;
                            $record->update([
                                'parent_id' => $parent->parent_id,
                                'order' => $parent->order + 1,
                            ]);
                            static::reorderSiblings($record->menu_id, $oldParentId);
                            static::reorderSiblings($record->menu_id, $parent->parent_id);
                        }
                    })
                    ->visible(fn (MenuItem $record): bool => $record->parent_id !== null),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit' => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['menu', 'parent', 'translations']);
    }

    protected static function reorderSiblings(int $menuId, ?int $parentId): void
    {
        $siblings = MenuItem::where('menu_id', $menuId)
            ->where('parent_id', $parentId)
            ->orderBy('order')
            ->get();

        foreach ($siblings as $index => $sibling) {
            $sibling->update(['order' => $index + 1]);
        }
    }
}
