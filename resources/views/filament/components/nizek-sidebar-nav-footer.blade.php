<div
    class="text-gray-500 px-4 py-6"
    x-data="{ mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }"
    x-init="
        window.addEventListener('dark-mode-toggled', e => {
            mode = e.detail
        });
    "
    :class="$store.sidebar.isOpen
        ? 'flex flex-row items-center justify-start gap-2 text-sm'
        : 'flex flex-col items-center justify-center gap-1'"
>
    <span
        :class="$store.sidebar.isOpen ? 'text-left text-sm' : 'text-center'"
        :style="$store.sidebar.isOpen ? '' : 'font-size: 10px'"
    >
        Powered By
    </span>

    <template x-if="mode === 'dark'">
        <img
            :width="$store.sidebar.isOpen ? 80 : 40"
            :height="$store.sidebar.isOpen ? 80 : 40"
            src="{{ asset('images/Nizek Logo - White.svg') }}"
        />
    </template>

    <template x-if="mode === 'light'">
        <img
            :width="$store.sidebar.isOpen ? 80 : 40"
            :height="$store.sidebar.isOpen ? 80 : 40"
            src="{{ asset('images/Nizek Logo - Black.svg') }}"
        />
    </template>
</div>

<script>
    const observer = new MutationObserver(() => {
        const isDark = document.documentElement.classList.contains('dark');
        window.dispatchEvent(new CustomEvent('dark-mode-toggled', {
            detail: isDark ? 'dark' : 'light'
        }));
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    });
</script>
