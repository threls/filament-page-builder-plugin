<?php

namespace Threls\FilamentPageBuilder;

use CactusGalaxy\FilamentAstrotomic\FilamentAstrotomicTranslatablePlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class PageBuilderPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-page-builder';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                Resources\PageResource::class,
                Resources\PageLayoutResource::class,
                Resources\BlueprintResource::class,
                Resources\RelationshipTypeResource::class,
            ])
            ->plugins([
                FilamentAstrotomicTranslatablePlugin::make(),
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }
}
