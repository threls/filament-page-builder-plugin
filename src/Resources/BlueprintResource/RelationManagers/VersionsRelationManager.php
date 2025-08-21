<?php

namespace Threls\FilamentPageBuilder\Resources\BlueprintResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Threls\FilamentPageBuilder\Resources\BlueprintResource;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Versions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->columns([
                TextColumn::make('version')
                    ->label('Version')
                    ->sortable()
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'published' => 'success',
                        'deprecated' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Published at')
                    ->dateTime()
                    ->since()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->defaultSort('version', 'desc')
            ->filters([])
            ->headerActions([])
            ->actions([
                Action::make('deprecate')
                    ->label('Deprecate')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status !== 'deprecated')
                    ->action(function ($record) {
                        $record->forceFill(['status' => 'deprecated'])->save();
                        Notification::make()
                            ->title('Version deprecated')
                            ->success()
                            ->send();
                    }),
                Action::make('useAsDraft')
                    ->label('Use as draft')
                    ->icon('heroicon-o-pencil-square')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $blueprint = $this->getOwnerRecord();
                        $schema = $record->schema ?? [];
                        $blueprint->forceFill([
                            'working_schema' => is_array($schema) ? $schema : [],
                            // Ensure we're editing a draft after adopting an older version
                            'status' => 'draft',
                        ])->save();

                        Notification::make()
                            ->title('Draft updated from version ' . $record->version)
                            ->success()
                            ->send();

                        // Refresh the edit form to reflect the updated working_schema.
                        $this->redirect(BlueprintResource::getUrl('edit', ['record' => $blueprint->getKey()]));
                    }),
            ])
            ->bulkActions([]);
    }
}
