<?php

namespace Threls\FilamentPageBuilder;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Threls\FilamentPageBuilder\Http\Middleware\SetApiLocale;

class PageBuilderServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-page-builder';

    public static string $viewNamespace = 'filament-page-builder';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('api')
            ->hasMigration('2025_01_28_120000_create_pages_table');
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        $this->app['router']->aliasMiddleware('api.locale', SetApiLocale::class);

    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        if (config('filament-page-builder.api.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }
    }
}
