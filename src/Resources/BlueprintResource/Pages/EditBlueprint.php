<?php

namespace Threls\FilamentPageBuilder\Resources\BlueprintResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Threls\FilamentPageBuilder\Resources\BlueprintResource;

class EditBlueprint extends EditRecord
{
    protected static string $resource = BlueprintResource::class;

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
}
