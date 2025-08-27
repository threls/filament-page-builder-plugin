<?php

namespace Threls\FilamentPageBuilder\Livewire;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Action;
use Threls\FilamentPageBuilder\Models\Menu;
use Threls\FilamentPageBuilder\Models\MenuItem;
use Threls\FilamentPageBuilder\Models\Page;
use CactusGalaxy\FilamentAstrotomic\Forms\Components\TranslatableTabs;
use CactusGalaxy\FilamentAstrotomic\TranslatableTab;
use Illuminate\Database\Eloquent\Collection;

class MenuItemsBuilder extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public Menu $menu;
    public Collection $menuItems;

    public function mount(Menu $menu): void
    {
        $this->menu = $menu;
        $this->loadMenuItems();
    }

    public function initializeComponent(): void
    {
    }

    public function loadMenuItems(): void
    {
        $this->menuItems = $this->menu->allMenuItems()
            ->orderBy('order')
            ->get();
    }

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Add Menu Item')
            ->icon('heroicon-m-plus')
            ->size('sm')
            ->color('primary')
            ->form($this->getFormSchema())
            ->action(fn (array $data) => $this->handleCreateAction($data));
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->icon('heroicon-m-pencil-square')
            ->iconButton()
            ->size('sm')
            ->color('gray')
            ->form($this->getFormSchema())
            ->fillForm(fn (array $arguments) => $this->fillFormData(MenuItem::find($arguments['record']['id'])))
            ->action(fn (array $arguments, array $data) => $this->handleEditAction($arguments, $data));
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->iconButton()
            ->size('sm')
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->action(fn (array $arguments) => $this->handleDeleteAction($arguments));
    }

    protected function handleCreateAction(array $data): void
    {
        $order = $this->menu->allMenuItems()->max('order') + 1;
        $translations = $this->extractTranslations($data);
        
        $menuItem = MenuItem::create([
            'menu_id' => $this->menu->id,
            'order' => $order,
            'type' => $data['type'],
            'page_id' => $data['page_id'] ?? null,
            'target' => $data['target'] ?? '_self',
            'is_visible' => $data['is_visible'] ?? true,
        ]);
        
        $this->handleIconUpload($menuItem, $data);
        $this->saveTranslations($menuItem, $translations);
        $this->loadMenuItems();
    }

    protected function handleEditAction(array $arguments, array $data): void
    {
        $record = MenuItem::find($arguments['record']['id']);
        
        $record->update([
            'type' => $data['type'],
            'page_id' => $data['page_id'] ?? null,
            'target' => $data['target'] ?? '_self',
            'is_visible' => $data['is_visible'] ?? true,
        ]);

        $this->handleIconUpload($record, $data);
        $this->saveTranslations($record, $this->extractTranslations($data));
        $this->loadMenuItems();
    }

    protected function handleDeleteAction(array $arguments): void
    {
        $record = MenuItem::find($arguments['record']['id']);
        $record->children()->update(['parent_id' => $record->parent_id]);
        $record->delete();
        $this->loadMenuItems();
    }

    protected function handleIconUpload(MenuItem $menuItem, array $data): void
    {
        if (isset($data['icon']) && !empty($data['icon'])) {
            $iconPath = is_array($data['icon']) ? $data['icon'][0] : $data['icon'];
            $menuItem->update(['icon' => $iconPath]);
        }
    }

    public function indent(MenuItem $item): void
    {
        $previousSibling = $this->menu->allMenuItems()
            ->where('parent_id', $item->parent_id)
            ->where('order', '<', $item->order)
            ->orderBy('order', 'desc')
            ->first();

        if (!$previousSibling || $previousSibling->getDepth() + 1 >= $this->menu->max_depth) {
            return;
        }

        $newOrder = $previousSibling->children()->max('order') + 1;
        $item->update([
            'parent_id' => $previousSibling->id,
            'order' => $newOrder
        ]);

        $this->reorderSiblings($item->parent_id);
        $this->loadMenuItems();
    }

    public function unindent(MenuItem $item): void
    {
        if (!$item->parent_id) {
            return;
        }

        $parent = $item->parent;
        $item->update([
            'parent_id' => $parent->parent_id,
            'order' => $parent->order + 1
        ]);

        $this->reorderSiblings($item->parent_id);
        $this->loadMenuItems();
    }

    public function moveUp(MenuItem $item): void
    {
        $previousSibling = $this->menu->allMenuItems()
            ->where('parent_id', $item->parent_id)
            ->where('order', '<', $item->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($previousSibling) {
            $tempOrder = $item->order;
            $item->update(['order' => $previousSibling->order]);
            $previousSibling->update(['order' => $tempOrder]);
            $this->loadMenuItems();
        }
    }

    public function moveDown(MenuItem $item): void
    {
        $nextSibling = $this->menu->allMenuItems()
            ->where('parent_id', $item->parent_id)
            ->where('order', '>', $item->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($nextSibling) {
            $tempOrder = $item->order;
            $item->update(['order' => $nextSibling->order]);
            $nextSibling->update(['order' => $tempOrder]);
            $this->loadMenuItems();
        }
    }

    protected function reorderSiblings(?int $parentId): void
    {
        $siblings = $this->menu->allMenuItems()
            ->where('parent_id', $parentId)
            ->orderBy('order')
            ->get();

        foreach ($siblings as $index => $sibling) {
            $sibling->update(['order' => $index + 1]);
        }
    }

    protected function getFormSchema(): array
    {
        return [
            $this->getBasicFieldsGrid(),
            $this->getTranslatableFields(),
        ];
    }

    protected function getBasicFieldsGrid(): Forms\Components\Grid
    {
        return Forms\Components\Grid::make(2)
            ->schema([
                $this->getTypeSelect(),
                $this->getPageSelect(),
                $this->getTargetSelect(),
                $this->getVisibilityToggle(),
                $this->getIconUpload(),
            ]);
    }

    protected function getTypeSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('type')
            ->label('Link Type')
            ->options([
                'parent' => 'Parent (No Link)',
                'page' => 'Page',
                'internal' => 'Internal Link',
                'external' => 'External Link',
            ])
            ->required()
            ->reactive()
            ->afterStateUpdated(fn (Set $set) => $set('page_id', null));
    }

    protected function getPageSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('page_id')
            ->label('Page')
            ->options(Page::query()->pluck('title', 'id'))
            ->searchable()
            ->visible(fn (Get $get): bool => $get('type') === 'page')
            ->required(fn (Get $get): bool => $get('type') === 'page');
    }

    protected function getTargetSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('target')
            ->label('Open In')
            ->options([
                '_self' => 'Same Window',
                '_blank' => 'New Window',
            ])
            ->default('_self')
            ->visible(fn (Get $get): bool => $get('type') !== 'parent');
    }

    protected function getVisibilityToggle(): Forms\Components\Toggle
    {
        return Forms\Components\Toggle::make('is_visible')
            ->label('Visible')
            ->default(true);
    }

    protected function getIconUpload(): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make('icon')
            ->label('Icon')
            ->image()
            ->panelLayout('grid')
            ->maxSize(1024)
            ->imageResizeMode('cover')
            ->imageCropAspectRatio('1:1')
            ->imageResizeTargetWidth('64')
            ->imageResizeTargetHeight('64')
            ->disk('public')
            ->directory('menu-icons')
            ->columnSpan(2);
    }

    protected function getTranslatableFields(): TranslatableTabs
    {
        return TranslatableTabs::make()
            ->localeTabSchema(fn(TranslatableTab $tab) => [
                Forms\Components\TextInput::make($tab->makeName('name'))
                    ->label('Name')
                    ->required($tab->isMainLocale())
                    ->maxLength(255),

                Forms\Components\TextInput::make($tab->makeName('url'))
                    ->label('URL')
                    ->maxLength(500)
                    ->visible(fn (Get $get): bool => in_array($get('type'), ['internal', 'external']))
                    ->required(fn (Get $get): bool => in_array($get('type'), ['internal', 'external']) && $tab->isMainLocale())
                    ->url(fn (Get $get): bool => $get('type') === 'external')
                    ->placeholder(fn (Get $get): string => 
                        $get('type') === 'external' ? 'https://example.com' : '/contact-us'
                    ),
            ]);
    }

    protected function extractTranslations(array $data): array
    {
        $translations = [];
        
        foreach ($this->getLocales() as $locale) {
            if (isset($data[$locale])) {
                $translations[$locale] = [
                    'name' => $data[$locale]['name'] ?? null,
                    'url' => $data[$locale]['url'] ?? null,
                ];
            }
        }
        
        return $translations;
    }

    protected function saveTranslations(MenuItem $menuItem, array $translations): void
    {
        foreach ($translations as $locale => $translationData) {
            if (!empty($translationData['name']) || !empty($translationData['url'])) {
                $menuItem->translateOrNew($locale)->fill($translationData)->save();
            }
        }
    }

    protected function fillFormData(MenuItem $record): array
    {
        $data = [
            'type' => $record->type,
            'page_id' => $record->page_id,
            'target' => $record->target,
            'is_visible' => $record->is_visible,
            'icon' => $record->icon ? [$record->icon] : [],
        ];
        
        foreach ($this->getLocales() as $locale) {
            $translation = $record->translate($locale);
            $data[$locale] = [
                'name' => $translation->name ?? '',
                'url' => $translation->url ?? '',
            ];
        }
        
        return $data;
    }

    protected function getLocales(): array
    {
        return array_keys(config('filament-page-builder.languages', ['en' => 'English']));
    }


    public function canIndent(MenuItem $item): bool
    {
        $previousSibling = $this->menu->allMenuItems()
            ->where('parent_id', $item->parent_id)
            ->where('order', '<', $item->order)
            ->orderBy('order', 'desc')
            ->first();

        if (!$previousSibling) {
            return false;
        }

        $currentDepth = $item->getDepth();
        return $currentDepth < ($this->menu->max_depth - 1);
    }

    public function canUnindent(MenuItem $item): bool
    {
        return $item->parent_id !== null;
    }

    public function render()
    {
        return view('filament-page-builder::livewire.menu-items-builder');
    }
}