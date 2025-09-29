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
            this.initSortableForContainer(`menu-items-${menuId}`, null);
            this.initNestedSortables();
        },

        initSortableForContainer(containerId, parentId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            new Sortable(container, {
                group: {
                    name: 'nested',
                    pull: true,
                    put: function(to, from, dragEl, evt) {
                        const targetDepth = parseInt(to.el.dataset.depth) || 0;
                        const maxDepth = parseInt(to.el.dataset.maxDepth) || {{ $menu->max_depth }};
                        return targetDepth <= maxDepth;
                    }
                },
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: (evt) => {
                    const newContainer = evt.to;
                    const newParentId = newContainer.dataset.parentId ? parseInt(newContainer.dataset.parentId) : null;
                    const itemIds = Array.from(newContainer.children)
                        .filter(el => el.dataset.itemId)
                        .map(el => parseInt(el.dataset.itemId));
                    @this.call('reorder', itemIds, newParentId);
                }
            });
        },

        initNestedSortables() {
            const nestedContainers = document.querySelectorAll('[id^="menu-items-"][data-parent-id]');
            nestedContainers.forEach(container => {
                const parentId = parseInt(container.dataset.parentId);
                this.initSortableForContainer(container.id, parentId);
            });
        }
    }
}
</script>

