<?php

namespace Threls\FilamentPageBuilder\Resources;

use Filament\Forms;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Threls\FilamentPageBuilder\Forms\Components\BreakpointFields;
use Threls\FilamentPageBuilder\Models\PageLayout;
use Threls\FilamentPageBuilder\Resources\PageLayoutResource\Pages\CreatePageLayout;
use Threls\FilamentPageBuilder\Resources\PageLayoutResource\Pages\EditPageLayout;
use Threls\FilamentPageBuilder\Resources\PageLayoutResource\Pages\ListPageLayouts;

class PageLayoutResource extends Resource
{
    protected static ?string $model = PageLayout::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Layouts';

    public static function getNavigationGroup(): ?string
    {
        return config('filament-page-builder.navigation_group', 'Content');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Layout details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, ?string $state) => $set('key', Str::slug($state))),
                        TextInput::make('key')
                            ->required()
                            ->readOnly(),
                        Toggle::make('is_active')->label('Active')->default(true),
                    ])->columns(2),
                Section::make('Layout configuration')
                    ->collapsible()
                    ->schema([
                        Select::make('width')
                            ->label('Width')
                            ->options([
                                'full' => 'Full',
                                'narrow' => 'Narrow',
                                'container' => 'Container',
                            ])
                            ->default('narrow')
                            ->native(false),

                        BreakpointFields::numberFlexible('Gap X (px)', 'gap-x', 'px'),
                        BreakpointFields::numberFlexible('Gap Y (px)', 'gap-y', 'px'),
                        BreakpointFields::selectFlexible('Flex Direction', 'flex-direction', [
                            'row' => 'Row',
                            'column' => 'Column',
                        ]),
                        BreakpointFields::selectFlexible('Flex Wrap', 'flex-wrap', [
                            'wrap' => 'wrap',
                            'nowrap' => 'nowrap',
                        ]),
                    ])
                    ->statePath('settings'),

                Section::make('Columns')
                    ->schema([
                        Repeater::make('columns')
                            ->relationship('columns')
                            ->hiddenLabel()
                            ->reorderable()
                            ->orderColumn('index')
                            ->collapsible()
                            ->itemLabel(function (array $state, $component): string {
                                $keys = array_keys($component->getState());
                                $index = array_search($state, $keys);
                                $label = $state['key'] ?? null;

                                return 'Column: ' . ($label ?? $index) . $index;
                            })
                            ->minItems(1)
                            ->maxItems(6)
                            ->schema([
                                TextInput::make('key')
                                    ->label('Key (optional)')
                                    ->maxLength(255)
                                    ->placeholder('e.g., left, main, sidebar')
                                    ->live(onBlur: true)
                                    ->nullable(),
                                BreakpointFields::numberFlexible('Weight', 'settings.weight', ''),
                                BreakpointFields::numberFlexible('Gap (px)', 'settings.gap-y', 'px'),
                                BreakpointFields::numberFlexible('Padding Left (px)', 'settings.padding-left', 'px'),
                                BreakpointFields::numberFlexible('Padding Right (px)', 'settings.padding-right', 'px'),
                                BreakpointFields::numberFlexible('Padding Top (px)', 'settings.padding-top', 'px'),
                                BreakpointFields::numberFlexible('Padding Bottom (px)', 'settings.padding-bottom', 'px'),

                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): EloquentBuilder
    {
        return parent::getEloquentQuery()->withCount('columns');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('key')->searchable()->sortable(),
                TextColumn::make('columns_count')->label('Columns')->sortable(),
                IconColumn::make('is_active')->boolean()->label('Active')->sortable(),
                TextColumn::make('updated_at')->dateTime()->since()->sortable(),
            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => ListPageLayouts::route('/'),
            'create' => CreatePageLayout::route('/create'),
            'edit' => EditPageLayout::route('/{record}/edit'),
        ];
    }
}
