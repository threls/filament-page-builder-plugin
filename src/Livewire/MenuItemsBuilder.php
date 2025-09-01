<?php

namespace Threls\FilamentPageBuilder\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Threls\FilamentPageBuilder\Models\Menu;
use Threls\FilamentPageBuilder\Models\MenuItem;

class MenuItemsBuilder extends Component
{
    public Menu $menu;

    public function mount(Menu $menu): void
    {
        $this->menu = $menu;
    }

    public function reorder(array $order, ?int $parentId = null): void
    {
        if (empty($order)) {
            return;
        }

        $this->updateItemPositions($order, $parentId);
        $this->notifyReorderSuccess();
    }

    private function updateItemPositions(array $order, ?int $parentId): void
    {
        $caseStatement = $this->buildOrderCaseStatement($order);

        MenuItem::whereIn('id', $order)->update([
            'order' => DB::raw($caseStatement),
            'parent_id' => $parentId,
        ]);

        $this->dispatch('menu-item:reordered');
    }

    private function buildOrderCaseStatement(array $order): string
    {
        $cases = array_map(
            fn ($itemId, $index) => "WHEN {$itemId} THEN " . ($index + 1),
            $order,
            array_keys($order)
        );

        return 'CASE id ' . implode(' ', $cases) . ' END';
    }

    private function notifyReorderSuccess(): void
    {
        Notification::make()
            ->success()
            ->title('Menu items reordered')
            ->send();
    }

    public function indent(int $itemId): void
    {
        $item = MenuItem::findOrFail($itemId);
        $previousSibling = $this->findPreviousSibling($item);

        if (!$this->canIndent($item, $previousSibling)) {
            return;
        }

        $this->indentItem($item, $previousSibling);
        $this->notifyItemIndented();
    }

    private function canIndent(MenuItem $item, ?MenuItem $previousSibling): bool
    {
        return $previousSibling && $item->getDepth() < ($this->menu->max_depth - 1);
    }

    private function indentItem(MenuItem $item, MenuItem $previousSibling): void
    {
        $newOrder = MenuItem::where('parent_id', $previousSibling->id)->max('order') + 1;
        
        $item->update([
            'parent_id' => $previousSibling->id,
            'order' => $newOrder ?? 1,
        ]);

        $this->reorderSiblings($item->parent_id);
        $this->dispatch('menu-item:updated');
    }

    private function findPreviousSibling(MenuItem $item): ?MenuItem
    {
        return MenuItem::where('menu_id', $this->menu->id)
            ->where('parent_id', $item->parent_id)
            ->where('order', '<', $item->order)
            ->orderBy('order', 'desc')
            ->first();
    }

    private function notifyItemIndented(): void
    {
        Notification::make()
            ->success()
            ->title('Item indented')
            ->send();
    }

    public function unindent(int $itemId): void
    {
        $item = MenuItem::findOrFail($itemId);

        if (!$item->parent_id) {
            return;
        }

        $this->unindentItem($item);
        $this->notifyItemUnindented();
    }

    private function unindentItem(MenuItem $item): void
    {
        $parent = $item->parent;
        
        $item->update([
            'parent_id' => $parent->parent_id,
            'order' => $parent->order + 1,
        ]);

        $this->reorderSiblings($parent->parent_id);
        $this->dispatch('menu-item:updated');
    }

    private function notifyItemUnindented(): void
    {
        Notification::make()
            ->success()
            ->title('Item unindented')
            ->send();
    }

    public function getMenuItemsProperty()
    {
        return $this->menu->allMenuItems()->with('children')->get();
    }

    public function deleteItem(int $itemId): void
    {
        $item = MenuItem::findOrFail($itemId);
        
        $this->reassignChildren($item);
        $this->removeItem($item);
        $this->notifyItemDeleted();
    }

    private function reassignChildren(MenuItem $item): void
    {
        $item->children()->update(['parent_id' => $item->parent_id]);
    }

    private function removeItem(MenuItem $item): void
    {
        $parentId = $item->parent_id;
        $item->delete();
        
        $this->reorderSiblings($parentId);
        $this->dispatch('menu-item:deleted');
    }

    private function notifyItemDeleted(): void
    {
        Notification::make()
            ->success()
            ->title('Menu item deleted')
            ->send();
    }

    protected function reorderSiblings(?int $parentId): void
    {
        MenuItem::where('menu_id', $this->menu->id)
            ->where('parent_id', $parentId)
            ->orderBy('order')
            ->get()
            ->each(fn ($sibling, $index) => $sibling->update(['order' => $index + 1]));
    }

    public function render()
    {
        return view('filament-page-builder::livewire.menu-items-builder');
    }
}
