<?php

namespace Threls\FilamentPageBuilder\Enums;

enum PageRelationshipTypeEnum: string
{
    case TESTIMONIAL = 'testimonial';
    case FAQ = 'faq';
    case EVENT = 'event';
    case FACTS = 'facts';
    case CONTRIBUTIONS = 'contributions';
    case SOCIAL_LINKS = 'social_links';
}
