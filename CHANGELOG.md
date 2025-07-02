# Changelog

All notable changes to `filament-page-builder` will be documented in this file.

## v2.2.0 - 2025-07-02

### Added

- New PageGridStyleEnum with NORMAL_GRID and STAGGERED_GRID options
- Enhanced HERO_SECTION block with optional sticker field and nullable image
- Enhanced IMAGE_CARDS block with optional title field at block level
- Enhanced BANNER block with optional description field (RichEditor)
- Enhanced KEY_VALUE_SECTION block with variant selector and image support for each item

**Full Changelog**: https://github.com/threls/filament-page-builder-plugin/compare/v2.1.3...v2.2.0

## v2.1.3 - 2025-07-02

**Full Changelog**: https://github.com/threls/filament-page-builder-plugin/compare/v2.1.0...v2.1.3

## Add Flysystem - 2025-07-02

**Full Changelog**: https://github.com/threls/filament-page-builder-plugin/compare/v2.1.0...v2.1.2

## v2.1.2 - 2025-07-02

### Added

- AWS S3 support via `league/flysystem-aws-s3-v3` package for cloud storage capabilities

## Add new page relationship types - 2025-07-01

**Full Changelog**: https://github.com/threls/filament-page-builder-plugin/compare/v2.1.0...v2.1.1

## v2.1.1 - 2025-01-07

### Added

- New page relationship types: Facts, Contributions, and Social Links
- Extended PageRelationshipTypeEnum with FACTS, CONTRIBUTIONS, and SOCIAL_LINKS cases

## v2.1.0 - Add HasPageBuilder trait - 2025-06-25

**Full Changelog**: https://github.com/threls/filament-page-builder-plugin/compare/v2.0.0...v2.1.0

## v2.0.0 - 2025-01-28

### Added

- Full multilingual support with translatable fields (title, slug, content)
- Media library integration with automatic image processing
- Responsive image conversions (thumbnail, medium, large sizes)
- API locale detection via Accept-Language header or query parameter
- Automatic temporary image processing and cleanup
- Enhanced data structures with MediaData support for better image handling
- Translation-friendly admin interface with tabbed forms
- SetApiLocale middleware for API internationalization

### Changed

- **BREAKING**: Image fields now return MediaData objects instead of simple string paths
- **BREAKING**: API responses now include locale information
- Page model now implements HasMedia interface for better media management
- Filament forms restructured to support multiple languages with tabs

### Dependencies

- Added `spatie/laravel-medialibrary: ^11.0` for media management
- Added `bezhansalleh/filament-language-switch: ^3.1` for language switching
- Added `spatie/laravel-translatable: ^6.7` for multilingual content

## v1.0.0 - 2025-01-28

### Added

- Initial release of Filament Page Builder plugin
  
- Multiple content blocks support:
  
  - Hero Section with title, subtitle, image, and CTA
  - Image Gallery with multiple images and optional button
  - Image Cards with repeatable card items
  - Horizontal Ticker with rotating content sections
  - Banner with title, text, image, and button
  - Rich Text with WYSIWYG editor
  - Key-Value Section for FAQ-style content
  - Map Location with coordinates and address
  - Relationship Content for displaying related models
  
- Drag & drop page builder interface using Filament's Builder component
  
- File upload management with configurable storage disks
  
- Page status management (Draft, Published, Archived)
  
- Comprehensive configuration system
  
- Type-safe data handling with Spatie Laravel Data
  
- Clean, extensible architecture
  
- Full Filament v3 integration
  
- Comprehensive documentation and examples
  
