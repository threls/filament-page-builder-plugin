<?php

namespace Threls\FilamentPageBuilder\Enums;

enum BlueprintFieldTypeEnum: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case RICH_TEXT = 'rich_text';
    case IMAGE = 'image';
    case GALLERY = 'gallery';
    case SELECT = 'select';
    case NUMBER = 'number';
    case TOGGLE = 'toggle';
    case COLOR = 'color';
    case LINK = 'link';
    case DATE = 'date';
    case DATETIME = 'datetime';
    case DATETIME_LOCAL = 'datetime_local';
    case TIME = 'time';
    case EMAIL = 'email';
    case URL = 'url';
    case RELATION = 'relation';

    public static function label(self $type): string
    {
        return match ($type) {
            self::TEXT => 'Text',
            self::TEXTAREA => 'Textarea',
            self::RICH_TEXT => 'Rich Text',
            self::IMAGE => 'Image',
            self::GALLERY => 'Gallery',
            self::SELECT => 'Select',
            self::NUMBER => 'Number',
            self::TOGGLE => 'Toggle',
            self::COLOR => 'Color',
            self::LINK => 'Link',
            self::DATE => 'Date',
            self::DATETIME => 'Date & Time',
            self::DATETIME_LOCAL => 'Date & Time (Local)',
            self::TIME => 'Time',
            self::EMAIL => 'Email',
            self::URL => 'URL',
            self::RELATION => 'Relation',
        };
    }

    /**
     * Options shown in the Blueprint editor type dropdown.
     * This purposefully excludes types currently not offered in the editor UI (e.g. datetime_local).
     */
    public static function optionsForEditor(): array
    {
        $allowed = [
            self::TEXT,
            self::TEXTAREA,
            self::RICH_TEXT,
            self::IMAGE,
            self::GALLERY,
            self::SELECT,
            self::NUMBER,
            self::TOGGLE,
            self::COLOR,
            self::LINK,
            self::DATE,
            self::RELATION,
        ];

        $options = [];
        foreach ($allowed as $case) {
            $options[$case->value] = self::label($case);
        }
        return $options;
    }
}
