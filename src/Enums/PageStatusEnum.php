<?php

namespace Threls\FilamentPageBuilder\Enums;

enum PageStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
}
