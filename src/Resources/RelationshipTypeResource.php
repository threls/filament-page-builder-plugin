<?php

namespace Threls\FilamentPageBuilder\Resources;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Threls\FilamentPageBuilder\Models\RelationshipType;
use Threls\FilamentPageBuilder\Resources\RelationshipTypeResource\Pages\CreateRelationshipType;
use Threls\FilamentPageBuilder\Resources\RelationshipTypeResource\Pages\EditRelationshipType;
use Threls\FilamentPageBuilder\Resources\RelationshipTypeResource\Pages\ListRelationshipTypes;

class RelationshipTypeResource extends Resource
{
    protected static ?string $model = RelationshipType::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Relationship Types';

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('filament-page-builder.permissions.can_manage_relationship_types', true);
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
                        TextInput::make('handle')->required()->unique(ignoreRecord: true),
                        TextInput::make('category')->label('Category')->maxLength(255)->nullable(),
                        Toggle::make('is_active')->default(true),
                        KeyValue::make('meta')
                            ->label('Meta (JSON)')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->helperText('Optional additional variables to be exposed in the API.')
                            ->addActionLabel('Add meta')
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('handle')->searchable()->sortable(),
                TextColumn::make('category')->toggleable()->sortable(),
                IconColumn::make('is_active')->boolean()->label('Active')->sortable(),
                TextColumn::make('updated_at')->dateTime()->since()->sortable(),
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
            'index' => ListRelationshipTypes::route('/'),
            'create' => CreateRelationshipType::route('/create'),
            'edit' => EditRelationshipType::route('/{record}/edit'),
        ];
    }
}
