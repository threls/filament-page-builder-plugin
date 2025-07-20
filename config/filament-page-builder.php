<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Page Builder Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the page builder settings for your application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Disk
    |--------------------------------------------------------------------------
    |
    | The default disk to use for file uploads in the page builder.
    |
    */
    'disk' => env('FILAMENT_PAGE_BUILDER_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Media Disk
    |--------------------------------------------------------------------------
    |
    | The disk to use for media library storage.
    |
    */
    'media_disk' => env('FILAMENT_PAGE_BUILDER_MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The navigation group for the Page resource in Filament admin panel.
    |
    */
    'navigation_group' => 'Content',

    /*
    |--------------------------------------------------------------------------
    | Navigation Icon
    |--------------------------------------------------------------------------
    |
    | The navigation icon for the Page resource in Filament admin panel.
    |
    */
    'navigation_icon' => 'heroicon-o-rectangle-stack',

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Configure the API settings for the page builder.
    |
    */
    'api' => [
        'enabled' => env('FILAMENT_PAGE_BUILDER_API_ENABLED', true),
        'prefix' => env('FILAMENT_PAGE_BUILDER_API_PREFIX', 'api'),
        'middleware' => ['api'],
    ],


    /* Available Languages */
    'languages' => [
        'en' => 'EN',
        'mt' => 'MT',
    ],
];
