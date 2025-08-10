<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Threls\FilamentPageBuilder\Enums\PageStatusEnum;
use Threls\FilamentPageBuilder\Models\Page;

class PageBuilderDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create a demo page with translated content blocks
        $page = Page::query()->create([
            'title' => 'Home',
            'slug' => 'home',
            'status' => PageStatusEnum::PUBLISHED->value,
        ]);

        // English content
        $page->translateOrNew('en')->content = [
            [
                'type' => 'hero-section',
                'data' => [
                    'title' => 'Welcome to the Playground',
                    'subtitle' => 'Develop and preview the Page Builder here',
                    'button-text' => 'Learn More',
                    'button-path' => '/about',
                ],
            ],
            [
                'type' => 'rich-text-page',
                'data' => [
                    'title' => 'Getting Started',
                    'content' => '<p>Edit content blocks in Filament, then open the API at <code>/api/pages</code>.</p>',
                ],
            ],
        ];

        // Maltese content (basic example)
        $page->translateOrNew('mt')->content = [
            [
                'type' => 'hero-section',
                'data' => [
                    'title' => 'Merħba fil-Playground',
                    'subtitle' => 'Żviluppa u ara l-bidliet hawn',
                    'button-text' => 'Aktar Informazzjoni',
                    'button-path' => '/about',
                ],
            ],
        ];

        $page->save();
    }
}
