<?php

namespace Threls\FilamentPageBuilder\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Threls\FilamentPageBuilder\Models\Menu;
use Threls\FilamentPageBuilder\Resources\MenuResource\Pages;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                self::getFormSchema()
            )
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('max_depth')
                    ->label('Max Depth')
                    ->sortable(),

                Tables\Columns\TextColumn::make('menuItems_count')
                    ->label('Items')
                    ->counts('menuItems'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
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
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Menu Information')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Menu Name')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->rows(2)
                                ->maxLength(500),

                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Select::make('location')
                                        ->label('Location')
                                        ->options(fn () => config('filament-page-builder.menus.locations', [
                                            'header' => 'Header',
                                            'footer' => 'Footer',
                                        ]))
                                        ->required()
                                        ->unique(ignoreRecord: true),

                                    Forms\Components\Select::make('status')
                                        ->label('Status')
                                        ->options([
                                            'active' => 'Active',
                                            'inactive' => 'Inactive',
                                        ])
                                        ->default('active')
                                        ->required(),

                                    Forms\Components\TextInput::make('max_depth')
                                        ->label('Max Depth')
                                        ->numeric()
                                        ->default(3)
                                        ->minValue(1)
                                        ->maxValue(5),
                                ]),
                        ]),
                ])
                ->columnSpan(['lg' => 1]),

            Forms\Components\Section::make('Menu Items')
                ->schema([
                    Forms\Components\ViewField::make('menu_items_builder')
                        ->view('filament-page-builder::components.menu-items-wrapper')
                        ->viewData(fn ($record) => ['menu' => $record]),
                ])
                ->columnSpan(['lg' => 2]),
        ];
    }
}
