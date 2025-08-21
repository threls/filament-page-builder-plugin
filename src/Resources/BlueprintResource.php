<?php

namespace Threls\FilamentPageBuilder\Resources;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Threls\FilamentPageBuilder\Models\Blueprint;
use Threls\FilamentPageBuilder\Models\RelationshipType;
use Threls\FilamentPageBuilder\Enums\BlueprintFieldTypeEnum;
use Threls\FilamentPageBuilder\Resources\BlueprintResource\Pages\CreateBlueprint;
use Threls\FilamentPageBuilder\Resources\BlueprintResource\Pages\EditBlueprint;
use Threls\FilamentPageBuilder\Resources\BlueprintResource\Pages\ListBlueprints;
use Threls\FilamentPageBuilder\Resources\BlueprintResource\RelationManagers\VersionsRelationManager;

class BlueprintResource extends Resource
{
    protected static ?string $model = Blueprint::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Blueprints';

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('filament-page-builder.permissions.can_manage_blueprints', true);
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
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'deprecated' => 'Deprecated',
                            ])->default('draft')->required(),
                        TextInput::make('label_template')->helperText('Optional. e.g. {title}')
                            ->maxLength(255)
                            ->nullable(),
                    ])->columns(2),

                Section::make('Fields')
                    ->statePath('working_schema')
                    ->schema([
                        Repeater::make('fields')
                            ->hiddenLabel()
                            ->reorderable()
                            ->schema([
                                TextInput::make('key')->required()->maxLength(100)->placeholder('e.g., title'),
                                TextInput::make('label')->required()->maxLength(255)->placeholder('Title'),
                                Select::make('type')
                                    ->required()
                                    ->options(BlueprintFieldTypeEnum::optionsForEditor())
                                    ->live()
                                    ->reactive(),
                                TextInput::make('help')->label('Help')->maxLength(255)->nullable(),
                                TextInput::make('default')->label('Default')->nullable(),
                                TextInput::make('rules')->label('Validation rules (pipe-separated)')->placeholder('required|max:120')->nullable(),

                                Section::make('Options')->collapsed()
                                    ->schema([
                                        // Common options
                                        TextInput::make('options.placeholder')->label('Placeholder')->maxLength(255)->nullable(),
                                        Toggle::make('options.multiple')->label('Multiple')->default(false)->reactive()
                                            ->visible(fn (Get $get) => in_array($get('type'), [BlueprintFieldTypeEnum::SELECT->value, BlueprintFieldTypeEnum::GALLERY->value], true)),
                                        // Media
                                        TextInput::make('options.collection')->label('Media Collection')->nullable()->reactive()
                                            ->visible(fn (Get $get) => in_array($get('type'), [BlueprintFieldTypeEnum::IMAGE->value, BlueprintFieldTypeEnum::GALLERY->value], true)),
                                        Repeater::make('options.conversions')->label('Conversions')->reactive()
                                            ->schema([
                                                TextInput::make('name')->label('Conversion name')->required(),
                                            ])->collapsed()
                                            ->visible(fn (Get $get) => in_array($get('type'), [BlueprintFieldTypeEnum::IMAGE->value, BlueprintFieldTypeEnum::GALLERY->value], true)),
                                        // Relation
                                        Select::make('options.relationship_type_handle')->reactive()
                                            ->label('Default Relationship Type')
                                            ->options(fn () => RelationshipType::query()->where('is_active', true)->pluck('name', 'handle'))
                                            ->searchable()
                                            ->preload()
                                            ->visible(fn (Get $get) => $get('type') === BlueprintFieldTypeEnum::RELATION->value),
                                        Select::make('options.allowed_relationship_type_handles')->reactive()
                                            ->label('Allowed Relationship Types')
                                            ->options(fn () => RelationshipType::query()->where('is_active', true)->pluck('name', 'handle'))
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Limit which relationship types can be selected in pages. Leave empty to allow all active types.')
                                            ->visible(fn (Get $get) => $get('type') === BlueprintFieldTypeEnum::RELATION->value),
                                    ])->columns(2),
                            ])->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('handle')->searchable()->sortable(),
                TextColumn::make('category')->toggleable()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'draft' => 'info',
                        'published' => 'success',
                        'deprecated' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => strtoupper((string) $state))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('latest_published_version')
                    ->label('Latest')
                    ->state(fn (Blueprint $record) => $record->publishedVersions()->max('version'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('versions_count')
                    ->label('Versions')
                    ->state(fn (Blueprint $record) => $record->versions()->count())
                    ->badge()
                    ->sortable(),
                TextColumn::make('updated_at')->dateTime()->since()->sortable(),
            ])
            ->actions([
                self::publishVersionTableAction(),
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Reusable action to publish the current working_schema as a new published version.
     */
    public static function publishVersionAction(): Actions\Action
    {
        return Actions\Action::make('publishVersion')
            ->label('Publish version')
            ->icon('heroicon-o-cloud-arrow-up')
            ->color('success')
            ->visible(fn () => (bool) config('filament-page-builder.permissions.can_manage_blueprints', true))
            ->requiresConfirmation()
            ->action(function (?Blueprint $record) {
                if (! $record) {
                    Notification::make()->title('No blueprint selected')->danger()->send();
                    return;
                }

                $next = $record->publishNewVersion();

                if ($next === null) {
                    Notification::make()
                        ->title('Nothing to publish')
                        ->body('The working schema is empty.')
                        ->danger()
                        ->send();
                    return;
                }

                Notification::make()
                    ->title("Blueprint published as v{$next}")
                    ->success()
                    ->send();
            });
    }

    /**
     * Reusable table row action to publish the current working_schema as a new version.
     */
    public static function publishVersionTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('publishVersion')
            ->label('Publish version')
            ->icon('heroicon-o-cloud-arrow-up')
            ->color('success')
            ->visible(fn () => (bool) config('filament-page-builder.permissions.can_manage_blueprints', true))
            ->requiresConfirmation()
            ->action(function (Blueprint $record) {
                $next = $record->publishNewVersion();

                if ($next === null) {
                    Notification::make()
                        ->title('Nothing to publish')
                        ->body('The working schema is empty.')
                        ->danger()
                        ->send();
                    return;
                }

                Notification::make()
                    ->title("Blueprint published as v{$next}")
                    ->success()
                    ->send();
            });
    }

    public static function getRelations(): array
    {
        return [
            VersionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlueprints::route('/'),
            'create' => CreateBlueprint::route('/create'),
            'edit' => EditBlueprint::route('/{record}/edit'),
        ];
    }
}
