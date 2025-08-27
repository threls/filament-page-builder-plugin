<div class="space-y-4" x-data wire:init="initializeComponent">
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-medium">Menu Items</h3>
        {{ $this->createAction }}
    </div>

    @if($menuItems->count() > 0)
        <div class="space-y-2">
            @foreach($menuItems->where('parent_id', null) as $item)
                @include('filament-page-builder::livewire.menu-item', ['item' => $item, 'level' => 0])
            @endforeach
        </div>
    @else
        <div class="text-gray-500 text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
            <p>No menu items yet. Click "Add Menu Item" to get started.</p>
        </div>
    @endif

    <x-filament-actions::modals />
</div>

