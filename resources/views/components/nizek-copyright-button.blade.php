@props([
    'variant' => 'login', // 'login', 'sidebar-open', 'sidebar-closed'
    'containerClass' => '',
])

@php
    $config = match($variant) {
        'sidebar-open' => [
            'containerClass' => 'relative',
            'containerStyle' => '',
            'width' => '280px',
            'height' => '45px',
            'borderRadius' => '11px',
            'poweredByTop' => '7px',
            'poweredByLeft' => '12px',
            'poweredBySize' => '8px',
            'iconTop' => '15px',
            'iconRight' => '12px',
            'iconSize' => '15px',
            'logoBottom' => '6px',
            'logoLeft' => '12px',
            'logoHeight' => '14px',
            'versionBottom' => '4px',
            'versionRight' => '160px',
            'versionSize' => '9px',
            'showVersion' => true,
            'showPoweredBy' => true,
            'showIcon' => true,
        ],
        'sidebar-closed' => [
            'containerClass' => 'relative',
            'containerStyle' => '',
            'width' => '80px',
            'height' => '55px',
            'borderRadius' => '11px',
            'poweredByTop' => '3px',
            'poweredByLeft' => '10px',
            'poweredBySize' => '8px',
            'iconTop' => '6px',
            'iconRight' => '6px',
            'iconSize' => '10px',
            'logoBottom' => '50%',
            'logoLeft' => '50%',
            'logoHeight' => '16px',
            'versionBottom' => '0px',
            'versionRight' => '0px',
            'versionSize' => '0px',
            'showVersion' => false,
            'showPoweredBy' => true,
            'showIcon' => false,
            'logoTransform' => 'translate(-50%, 50%)',
        ],
        default => [ // login
            'containerClass' => '',
            'containerStyle' => 'position: fixed; bottom: 1rem; left: 0; right: 0; display: flex; justify-content: center; z-index: 9999;',
            'width' => '170px',
            'height' => '45px',
            'borderRadius' => '11px',
            'poweredByTop' => '7px',
            'poweredByLeft' => '12px',
            'poweredBySize' => '8px',
            'iconTop' => '15px',
            'iconRight' => '12px',
            'iconSize' => '15px',
            'logoBottom' => '6px',
            'logoLeft' => '12px',
            'logoHeight' => '14px',
            'versionBottom' => '4px',
            'versionRight' => '70px',
            'versionSize' => '9px',
            'showVersion' => true,
            'showPoweredBy' => true,
            'showIcon' => true,
        ],
    };
@endphp

<div
    class="{{ $config['containerClass'] }} {{ $containerClass }}"
    style="{{ $config['containerStyle'] }}"
    x-data="{ isDark: document.documentElement.classList.contains('dark'), init() { const observer = new MutationObserver(() => { this.isDark = document.documentElement.classList.contains('dark'); }); observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] }); } }"
>
    <a
        href="https://nizek.com/"
        target="_blank"
        rel="noopener noreferrer"
        class="no-underline block relative"
        :class="isDark ? 'bg-gray-900' : 'bg-white'"
        style="width: {{ $config['width'] }}; height: {{ $config['height'] }}; border-radius: {{ $config['borderRadius'] }}; border: 1px solid #EEEEEE; outline: none; list-style: none; overflow: hidden;"
    >
        {{-- Dark mode border --}}
        <div x-cloak :class="isDark ? 'block' : 'hidden'" class="absolute inset-0 border border-gray-700 pointer-events-none" style="border-radius: {{ $config['borderRadius'] }};"></div>

        @if($config['showPoweredBy'])
            {{-- Top: "Powered by" --}}
            <span
                class="absolute"
                style="top: {{ $config['poweredByTop'] }}; left: {{ $config['poweredByLeft'] }}; font-size: {{ $config['poweredBySize'] }}; color: #999999; letter-spacing: 0.02em;"
            >Powered By</span>
        @endif

        @if($config['showIcon'])
            {{-- Top right: External link icon --}}
            <x-filament::icon
                icon="heroicon-o-arrow-top-right-on-square"
                class="absolute"
                style="top: {{ $config['iconTop'] }}; right: {{ $config['iconRight'] }}; width: {{ $config['iconSize'] }}; height: {{ $config['iconSize'] }};"
            />
        @endif

        {{-- Nizek Logo --}}
        <img
            x-cloak
            :class="isDark ? 'hidden' : 'block'"
            src="{{ asset('images/nizek-logo-black.svg') }}"
            alt="Nizek"
            class="absolute"
            style="bottom: {{ $config['logoBottom'] }}; left: {{ $config['logoLeft'] }}; height: {{ $config['logoHeight'] }}; {{ isset($config['logoTransform']) ? 'transform: ' . $config['logoTransform'] . ';' : '' }}"
        />

        <img
            x-cloak
            :class="isDark ? 'block' : 'hidden'"
            src="{{ asset('images/nizek-logo-white.svg') }}"
            alt="Nizek"
            class="absolute"
            style="bottom: {{ $config['logoBottom'] }}; left: {{ $config['logoLeft'] }}; height: {{ $config['logoHeight'] }}; {{ isset($config['logoTransform']) ? 'transform: ' . $config['logoTransform'] . ';' : '' }}"
        />

        @if($config['showVersion'])
            {{-- Version --}}
            <span
                class="absolute"
                :class="isDark ? 'text-white' : 'text-black'"
                style="bottom: {{ $config['versionBottom'] }}; right: {{ $config['versionRight'] }}; font-size: {{ $config['versionSize'] }}; font-weight: 500;"
            >v{{ config('app.version') }}</span>
        @endif
    </a>
</div>
