<div class="border border-gray-200 rounded-lg p-3 {{ $level > 0 ? 'ml-8' : '' }}">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            @if($item->getIconUrl())
                <img src="{{ $item->getIconUrl() }}" alt="Icon" class="w-6 h-6">
            @endif

            <div>
                <div class="font-medium">{{ $item->name }}</div>
                <div class="text-sm text-gray-500">
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

        <div class="flex items-center space-x-0.5">
            @if($this->canIndent($item))
                <button wire:click="indent({{ $item->id }})"
                        class="p-0.5 text-gray-400 hover:text-gray-600 transition-colors"
                        title="Indent">
                    <svg class="w-3.5 h-3.5" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            @endif

            @if($this->canUnindent($item))
                <button wire:click="unindent({{ $item->id }})"
                        class="p-0.5 text-gray-400 hover:text-gray-600 transition-colors"
                        title="Unindent">
                    <svg class="w-3.5 h-3.5" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
            @endif

            <button wire:click="moveUp({{ $item->id }})"
                    class="p-0.5 text-gray-400 hover:text-gray-600 transition-colors"
                    title="Move Up">
                <svg class="w-3.5 h-3.5" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                </svg>
            </button>

            <button wire:click="moveDown({{ $item->id }})"
                    class="p-0.5 text-gray-400 hover:text-gray-600 transition-colors"
                    title="Move Down">
                <svg class="w-3.5 h-3.5" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            {{ ($this->editAction)(['record' => $item]) }}
            {{ ($this->deleteAction)(['record' => $item]) }}
        </div>
    </div>

    @if($item->children->count() > 0)
        <div class="mt-3 space-y-2">
            @foreach($item->children as $child)
                @include('filament-page-builder::livewire.menu-item', ['item' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>
