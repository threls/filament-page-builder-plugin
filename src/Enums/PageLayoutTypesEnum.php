<?php

namespace Threls\FilamentPageBuilder\Enums;

enum PageLayoutTypesEnum: string
{
    case HERO_SECTION = 'hero-section';
    case IMAGE_GALLERY = 'image-gallery';
//    case HORIZONTAL_TICKER = 'horizontal-ticker';
    case BANNER = 'banner';
    case SECTION = 'section';
    case RICH_TEXT_PAGE = 'rich-text-page';
    case IMAGE_AND_RICH_TEXT = 'image-and-rich-text';
    case KEY_VALUE_SECTION = 'key-value-section';
//    case MAP_LOCATION = 'map-location';
//    case IMAGE_CARDS = 'image-cards';
    case RELATIONSHIP_CONTENT = 'relationship-content';
    case DIVIDER = 'divider';
    case VIDEO_EMBEDDER = 'video-embedder';
}
