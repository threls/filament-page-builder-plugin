<div class="space-y-4" x-data="menuBuilder({{ $menu->id }})">

    @if($this->menuItems->where('parent_id', null)->count() > 0)
        <ul class="space-y-2" id="menu-items-{{ $menu->id }}" data-menu-id="{{ $menu->id }}">
            @foreach($this->menuItems->where('parent_id', null)->sortBy('order') as $item)
                @include('filament-page-builder::components.menu-item-row', [
                    'item' => $item,
                    'menuItems' => $this->menuItems,
                    'depth' => 0
                ])
            @endforeach
        </ul>
    @else
        <div class="text-gray-500 dark:text-gray-400 text-center py-8 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <p class="mt-2">No menu items yet. Click "Add Menu Item" to get started.</p>
        </div>
    @endif
</div>

<script>
function menuBuilder(menuId) {
    return {
        init() {
            this.$nextTick(() => {
                if (typeof Sortable !== 'undefined') {
                    this.initSortable();
                }
            });
        },

        initSortable() {
            const container = document.getElementById(`menu-items-${menuId}`);
            if (!container) return;

            new Sortable(container, {
                group: 'nested',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: (evt) => {
                    const itemIds = Array.from(container.children).map(el =>
                        parseInt(el.dataset.itemId)
                    );
                    @this.call('reorder', itemIds, null);
                }
            });
        }
    }
}
</script>

