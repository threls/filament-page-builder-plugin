# Filament Page Builder

A flexible page builder plugin for Filament v3 with multiple content blocks, perfect for creating dynamic pages in your Laravel applications.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/threls/filament-page-builder.svg?style=flat-square)](https://packagist.org/packages/threls/filament-page-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/threls/filament-page-builder.svg?style=flat-square)](https://packagist.org/packages/threls/filament-page-builder)

## Features

- **Multiple Content Blocks**: Hero sections, image galleries, banners, rich text, and more
- **Drag & Drop Builder**: Visual page building with Filament's Builder component
- **File Management**: Integrated file uploads with configurable storage disks
- **Status Management**: Draft, Published, and Archived page states
- **Relationship Content**: Display related content like testimonials, FAQs, events
- **Configurable**: Customizable settings and extensible block system
- **Clean Code**: Well-structured with Data Transfer Objects and proper separation of concerns

## Installation

You can install the package via composer:

```bash
composer require threls/filament-page-builder
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-page-builder-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-page-builder-config"
```

## Usage

### Basic Setup

1. Install the plugin in your Filament panel:

```php
use Threls\FilamentPageBuilder\PageBuilderPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            PageBuilderPlugin::make(),
        ]);
}
```

### Available Content Blocks

The page builder comes with the following content blocks out of the box:

- **Hero Section**: Large banner with title, subtitle, image, and call-to-action button
- **Image Gallery**: Collection of images with optional text and button
- **Image Cards**: Repeatable cards with image, text, and button
- **Horizontal Ticker**: Rotating content sections with images and descriptions
- **Banner**: Simple banner with title, text, image, and button
- **Rich Text**: WYSIWYG text editor content
- **Key-Value Section**: FAQ-style content with title/description pairs
- **Map Location**: Geographic location with coordinates and address
- **Relationship Content**: Display related models (testimonials, FAQs, events)

### Configuration

The package publishes a configuration file where you can customize:

```php
return [
    // Default storage disk for uploads
    'disk' => env('FILAMENT_PAGE_BUILDER_DISK', 'public'),

    // Navigation settings
    'navigation_group' => 'Content',
    'navigation_icon' => 'heroicon-o-rectangle-stack',

    // Relationship types
    'relationship_types' => [
        'testimonial' => 'Testimonials',
        'faq' => 'FAQs',
        'event' => 'Events',
    ],

    // Custom blocks (extend functionality)
    'custom_blocks' => [],

    // API settings
    'api' => [
        'enabled' => env('FILAMENT_PAGE_BUILDER_API_ENABLED', true),
        'prefix' => env('FILAMENT_PAGE_BUILDER_API_PREFIX', 'api'),
        'middleware' => ['api'],
    ],
];
```

### Working with Pages

#### Creating Pages

Pages can be created through the Filament admin interface. Each page has:
- Title (automatically generates slug)
- Status (Draft, Published, Archived)
- Content blocks (built using the visual builder)

#### Retrieving Page Data

You can retrieve and work with page data using the included Data Transfer Objects:

```php
use Threls\FilamentPageBuilder\Models\Page;
use Threls\FilamentPageBuilder\Data\PageData;

$page = Page::where('slug', 'home')->first();
$pageData = PageData::fromModel($page);

// Access structured content
foreach ($pageData->content as $block) {
    echo $block->type; // e.g., 'hero-section'
    // $block->data contains the structured block data
}
```

#### Using the API

The package includes a built-in API for retrieving page data:

```php
// Available endpoints:
// GET /api/pages - Returns all published pages
// You can filter by slug using query parameters: /api/pages?filter[slug]=home

// Example controller usage in your app:
Route::get('pages', function() {
    return Http::get('https://yourdomain.com/api/pages')->json();
});

Route::get('pages/{slug}', function($slug) {
    return Http::get("https://yourdomain.com/api/pages?filter[slug]={$slug}")->json();
});
```

You can configure the API in the config file:

```php
// In your config/filament-page-builder.php
'api' => [
    'enabled' => env('FILAMENT_PAGE_BUILDER_API_ENABLED', true),
    'prefix' => env('FILAMENT_PAGE_BUILDER_API_PREFIX', 'api'),
    'middleware' => ['api'],
],
```

### Extending the Page Builder

#### Adding Custom Blocks

You can add custom blocks by extending the configuration:

```php
// In your config/filament-page-builder.php
'custom_blocks' => [
    'testimonial-carousel' => [
        'label' => 'Testimonial Carousel',
        'schema' => [
            TextInput::make('title')->required(),
            Repeater::make('testimonials')->schema([
                TextInput::make('name')->required(),
                Textarea::make('quote')->required(),
                FileUpload::make('avatar')->image(),
            ]),
        ]
    ]
],
```

#### Customizing Relationship Types

Modify the `relationship_types` configuration to match your application's models:

```php
'relationship_types' => [
    'product' => 'Products',
    'service' => 'Services',
    'team_member' => 'Team Members',
],
```

## Data Structure

The package uses Spatie's Laravel Data for type-safe data handling:

- `PageData`: Main page data structure
- `ContentBlockData`: Individual content block wrapper
- Component-specific data classes: `HeroSectionData`, `ImageGalleryData`, etc.

## Requirements

- PHP 8.2 or higher (including PHP 8.4)
- Laravel 10.0 or higher
- Filament 3.2 or higher
- Spatie Laravel Query Builder 6.3 or higher (for API functionality)

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Threls](https://github.com/threls)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.