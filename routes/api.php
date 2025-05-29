<?php

use Illuminate\Support\Facades\Route;
use Threls\FilamentPageBuilder\Http\Controllers\PageController;

$prefix = config('filament-page-builder.api.prefix', 'api');
$middleware = config('filament-page-builder.api.middleware', ['api']);

Route::prefix($prefix)->middleware($middleware)->group(function () {
    Route::get('pages', PageController::class)->name('page-builder.api.pages');
});
