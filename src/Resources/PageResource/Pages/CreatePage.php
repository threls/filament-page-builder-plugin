<?php

namespace Threls\FilamentPageBuilder\Resources\PageResource\Pages;

use CactusGalaxy\FilamentAstrotomic\Resources\Pages\Record\CreateTranslatable;
use Filament\Resources\Pages\CreateRecord;
use Threls\FilamentPageBuilder\Resources\PageResource;
use Threls\FilamentPageBuilder\Models\Page;

class CreatePage extends CreateRecord
{
    use CreateTranslatable;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    protected function afterCreate(): void
    {
        /** @var Page $page */
        $page = $this->record;

        // Duplicate the initially entered content to other configured languages
        // only when exactly one locale has content on creation time.
        $languages = array_keys((array) config('filament-page-builder.languages', []));
        if (count($languages) <= 1) {
            return;
        }

        // Find locales that currently have non-empty content
        $filledLocales = [];
        foreach ($languages as $locale) {
            $tr = $page->translate($locale, false); // do not fallback
            $content = $tr?->content ?? null;
            $hasContent = is_array($content) ? ! empty($content) : ($content !== null && $content !== '');
            if ($hasContent) {
                $filledLocales[] = $locale;
            }
        }

        if (count($filledLocales) !== 1) {
            return; // more than one or none filled -> do nothing
        }

        $sourceLocale = $filledLocales[0];
        $sourceContent = (array) ($page->translate($sourceLocale, false)?->content ?? []);

        foreach ($languages as $locale) {
            if ($locale === $sourceLocale) {
                continue;
            }
            $tr = $page->translate($locale, false);
            $content = $tr?->content ?? null;
            $hasContent = is_array($content) ? ! empty($content) : ($content !== null && $content !== '');
            if ($hasContent) {
                continue; // user explicitly provided content for this locale
            }
            $page->translateOrNew($locale)->content = $sourceContent;
        }

        $page->save();
    }
}
