<li class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 shadow-sm"
    data-item-id="{{ $item->id }}">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="drag-handle cursor-move p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                </svg>
            </div>

            <div class="flex-1">
                <div class="font-medium text-gray-900 dark:text-gray-100">
                    {{ $item->name ?? 'Untitled' }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ ucfirst($item->type) }}
                    @if($item->url)
                        • {{ $item->url }}
                    @endif
                    @if(!$item->is_visible)
                        • <span class="text-red-500">Hidden</span>
                    @endif
                </div>
            </div>
        </div>

        @php
            if (!isset($menuItems)) {
                $menuItems = \Threls\FilamentPageBuilder\Models\MenuItem::where('menu_id', $item->menu_id)->get();
            }
            $siblings = $menuItems->where('parent_id', $item->parent_id);
            $canIndent = $siblings->where('order', '<', $item->order)->isNotEmpty() && $item->getDepth() < ($item->menu->max_depth - 1);
        @endphp

        <div class="flex items-center space-x-1">

            @if($canIndent)
                <button wire:click="indent({{ $item->id }})"
                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                        title="Indent">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @endif

            @if($item->parent_id)
                <button wire:click="unindent({{ $item->id }})"
                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                        title="Unindent">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            @endif

            <a href="{{ \Threls\FilamentPageBuilder\Resources\MenuItemResource::getUrl('edit', ['record' => $item->id]) }}"
               class="p-1 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors hover:scale-110"
               title="Edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </a>

            <button wire:click="deleteItem({{ $item->id }})"
                    wire:confirm="Are you sure you want to delete this menu item?"
                    class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                    title="Delete">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </div>

    <ul class="mt-3 space-y-2 ml-6 min-h-[8px]" id="menu-items-{{ $item->id }}" data-parent-id="{{ $item->id }}" data-depth="{{ $depth + 1 }}" data-max-depth="{{ $item->menu->max_depth }}">
        @foreach($item->children->sortBy('order') as $child)
            @include('filament-page-builder::components.menu-item-row', [
                'item' => $child,
                'menuItems' => $menuItems,
                'depth' => $depth + 1
            ])
        @endforeach
    </ul>
</li>
