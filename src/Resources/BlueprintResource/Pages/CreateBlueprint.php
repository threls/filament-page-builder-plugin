<?php

namespace Threls\FilamentPageBuilder\Resources\BlueprintResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Threls\FilamentPageBuilder\Models\Blueprint;
use Threls\FilamentPageBuilder\Resources\BlueprintResource;

class CreateBlueprint extends CreateRecord
{
    protected static string $resource = BlueprintResource::class;

    protected function afterCreate(): void
    {
        /** @var Blueprint $record */
        $record = $this->record;

        if ($record->status === 'published') {
            $version = $record->publishNewVersion();

            if ($version !== null) {
                Notification::make()
                    ->title("Blueprint created and published as v{$version}")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Blueprint created but no version published')
                    ->body('The working schema is empty.')
                    ->warning()
                    ->send();
            }
        }
    }
}
