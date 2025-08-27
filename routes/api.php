<?php

use Illuminate\Support\Facades\Route;
use Threls\FilamentPageBuilder\Http\Controllers\PageController;
use Threls\FilamentPageBuilder\Http\Controllers\MenuController;

$prefix = config('filament-page-builder.api.prefix', 'api');
$middleware = array_merge(
    config('filament-page-builder.api.middleware', ['api']),
    ['api.locale']
);

Route::prefix($prefix)->middleware($middleware)->group(function () {
    Route::get('pages', PageController::class)->name('page-builder.api.pages');
    Route::get('menus', MenuController::class)->name('page-builder.api.menus');
});
