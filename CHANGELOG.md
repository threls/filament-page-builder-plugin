# Changelog

All notable changes to `filament-page-builder` will be documented in this file.

## v2.1.1 - 2025-01-07

### Added
- New page relationship types: Facts, Contributions, and Social Links
- Extended PageRelationshipTypeEnum with FACTS, CONTRIBUTIONS, and SOCIAL_LINKS cases

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