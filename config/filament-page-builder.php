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
    | The disk to use for media library storage. This is used for processed
    | images with conversions (thumbnail, medium, large).
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
    | Enable Relationships
    |--------------------------------------------------------------------------
    |
    | Whether to enable relationship content blocks in the page builder.
    |
    */
    'enable_relationships' => true,

    /*
    |--------------------------------------------------------------------------
    | Available Relationship Types
    |--------------------------------------------------------------------------
    |
    | Define which relationship types are available in the page builder.
    | You can customize this based on your application's needs.
    |
    */
    'relationship_types' => [
        'testimonial' => 'Testimonials',
        'faq' => 'FAQs',
        'event' => 'Events',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Blocks
    |--------------------------------------------------------------------------
    |
    | Register custom blocks for the page builder.
    | Each block should have a unique key and a corresponding form schema.
    |
    */
    'custom_blocks' => [
        // Example:
        // 'custom-block' => [
        //     'label' => 'Custom Block',
        //     'schema' => [
        //         // Filament form components
        //     ]
        // ]
    ],

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

    /*
    |--------------------------------------------------------------------------
    | Available Languages
    |--------------------------------------------------------------------------
    |
    | Configure available languages for the page builder.
    | These languages will be used for the Filament language switcher
    | and for creating translatable content tabs.
    |
    */
    'languages' => [
        'en' => 'English',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | The default language for the page builder.
    |
    */
    'default_language' => 'en',
];
