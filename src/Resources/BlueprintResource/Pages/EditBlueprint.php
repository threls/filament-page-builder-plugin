<?php

namespace Threls\FilamentPageBuilder\Resources\BlueprintResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Threls\FilamentPageBuilder\Models\Blueprint;
use Threls\FilamentPageBuilder\Resources\BlueprintResource;

class EditBlueprint extends EditRecord
{
    protected static string $resource = BlueprintResource::class;

    protected array $originalAttributes = [];

    protected function getHeaderActions(): array
    {
        return [
            BlueprintResource::publishVersionAction()
                ->after(function () {
                    // Ensure the Versions relation manager refreshes by reloading the page
                    $this->redirect(BlueprintResource::getUrl('edit', ['record' => $this->record->getKey()]));
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Blueprint $record */
        $record = $this->record;
        $this->originalAttributes = $record->getAttributes();

        return parent::mutateFormDataBeforeFill($data);
    }

    protected function afterSave(): void
    {
        /** @var Blueprint $record */
        $record = $this->record;

        if ($record->status === 'published' && $this->wasModelDirty()) {
            $version = $record->publishNewVersion();

            if ($version !== null) {
                Notification::make()
                    ->title("Changes saved and published as v{$version}")
                    ->success()
                    ->send();

                $this->redirect(BlueprintResource::getUrl('edit', ['record' => $this->record->getKey()]));
            }
        }
    }

    protected function wasModelDirty(): bool
    {
        /** @var Blueprint $record */
        $record = $this->record;
        $currentAttributes = $record->getAttributes();

        foreach ($this->originalAttributes as $key => $value) {
            if (! array_key_exists($key, $currentAttributes)) {
                continue;
            }

            $originalValue = $value;
            $currentValue = $currentAttributes[$key];

            if (is_array($originalValue)) {
                $originalValue = json_encode($originalValue);
            }
            if (is_array($currentValue)) {
                $currentValue = json_encode($currentValue);
            }

            if ($originalValue !== $currentValue) {
                return true;
            }
        }

        return false;
    }
}
