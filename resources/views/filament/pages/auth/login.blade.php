@props([
    'heading' => null,
    'subheading' => null,
])

@php
    $heading ??= $this->getHeading();
    $subheading ??= $this->getSubHeading();
    $hasLogo = $this->hasLogo();
@endphp

<div>
    <div {{ $attributes->class(['fi-simple-page']) }}>
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_PAGE_START, scopes: $this->getRenderHookScopes()) }}

        <div class="fi-simple-page-content relative">
            {{-- Theme Toggle Switch - Top Right --}}
            <div class="absolute top-0 z-10" style="right: 0;"
                x-data="{
                    isDark: document.documentElement.classList.contains('dark'),
                    toggle() {
                        this.isDark = !this.isDark;
                        if (this.isDark) {
                            document.documentElement.classList.add('dark');
                            localStorage.setItem('theme', 'dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                            localStorage.setItem('theme', 'light');
                        }
                    }
                }"
            >
                <button
                    type="button"
                    role="switch"
                    :aria-checked="isDark"
                    @click="toggle()"
                    :style="isDark ? 'background-color: rgb(36 39 44)' : 'background-color: rgb(209 213 219)'"
                    class="fi-toggle relative inline-grid h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent shadow-sm transition-colors duration-200 ease-in-out outline-none"
                >
                    <span
                        :style="isDark ? 'transform: translateX(1.25rem); background-color: white' : 'transform: translateX(0); background-color: black'"
                        class="col-start-1 row-start-1 pointer-events-none h-6 w-6 rounded-full shadow-sm transition duration-200 ease-in-out flex items-center justify-center"
                    >
                        {{-- Sun icon - shown in light mode --}}
                        <x-filament::icon
                            icon="heroicon-s-sun"
                            x-show="!isDark"
                            class="h-4 w-4 text-white relative"
                        />
                        {{-- Moon icon - shown in dark mode --}}
                        <x-filament::icon
                            icon="heroicon-s-moon"
                            x-show="isDark"
                            class="h-4 w-4 text-black relative"
                        />
                    </span>
                </button>
            </div>

            @if (filled($heading) || $hasLogo || filled($subheading))
                <x-filament-panels::header.simple
                    :heading="$heading"
                    :logo="$hasLogo"
                    :subheading="$subheading"
                />
            @endif

            {{ $this->content }}
        </div>

        @if (! $this instanceof \Filament\Tables\Contracts\HasTable)
            <x-filament-actions::modals />
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_PAGE_END, scopes: $this->getRenderHookScopes()) }}
    </div>

    {{-- Bottom Logo - Fixed at bottom of page --}}
    <x-nizek-copyright-button />
</div>
