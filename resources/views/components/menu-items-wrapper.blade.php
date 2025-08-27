<div>
    @if($menu)
        @livewire('menu-items-builder', ['menu' => $menu])
    @else
        <div class="text-gray-500 text-center py-8">
            <p>Save the menu first to add menu items.</p>
        </div>
    @endif
</div>