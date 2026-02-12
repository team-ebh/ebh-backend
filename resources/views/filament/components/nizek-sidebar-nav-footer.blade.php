<div
    class="px-4 py-6"
    :class="$store.sidebar.isOpen
        ? 'flex flex-row items-center justify-start gap-2'
        : 'flex flex-col items-center justify-center gap-1'"
>
    {{-- Sidebar open --}}
    <template x-if="$store.sidebar.isOpen">
        <div>
            <x-nizek-copyright-button variant="sidebar-open" />
        </div>
    </template>

    {{-- Sidebar closed --}}
    <template x-if="!$store.sidebar.isOpen">
        <div>
            <x-nizek-copyright-button variant="sidebar-closed" />
        </div>
    </template>
</div>
