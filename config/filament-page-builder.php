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

    'permissions' => [
        'can_create' => env('FILAMENT_PAGE_BUILDER_CAN_CREATE', true),
        'can_delete' => env('FILAMENT_PAGE_BUILDER_CAN_DELETE', true),
        // Admin management abilities
        'can_manage_layouts' => env('FILAMENT_PAGE_BUILDER_CAN_MANAGE_LAYOUTS', true),
        'can_manage_blueprints' => env('FILAMENT_PAGE_BUILDER_CAN_MANAGE_BLUEPRINTS', true),
        'can_manage_relationship_types' => env('FILAMENT_PAGE_BUILDER_CAN_MANAGE_REL_TYPES', true),
        'can_manage_compositions' => env('FILAMENT_PAGE_BUILDER_CAN_MANAGE_COMPOSITIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Locations
    |--------------------------------------------------------------------------
    |
    | Define available menu locations for your application.
    | You can add or remove locations as needed.
    |
    */
    'menus' => [
        'locations' => [
            'header' => 'Header',
            'footer' => 'Footer',
            'sidebar' => 'Sidebar',
        ],
    ],
];
