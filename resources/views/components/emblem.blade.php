@php $uid = 'em'.Str::random(6); @endphp
<svg {{ $attributes }} viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Ornamen">
    <defs>
        <linearGradient id="{{ $uid }}-gold" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#f0dc93"/>
            <stop offset=".5" stop-color="#dfb035"/>
            <stop offset="1" stop-color="#a97418"/>
        </linearGradient>
        <radialGradient id="{{ $uid }}-green" cx=".35" cy=".3" r=".9">
            <stop offset="0" stop-color="#1f8a5e"/>
            <stop offset="1" stop-color="#06241a"/>
        </radialGradient>
    </defs>
    <circle cx="32" cy="32" r="31" fill="url(#{{ $uid }}-gold)"/>
    <circle cx="32" cy="32" r="27.5" fill="url(#{{ $uid }}-green)"/>
    <g fill="none" stroke="url(#{{ $uid }}-gold)" stroke-width="1.1" opacity=".9">
        <rect x="18" y="18" width="28" height="28" rx="1"/>
        <rect x="18" y="18" width="28" height="28" rx="1" transform="rotate(45 32 32)"/>
    </g>
    <path d="M36.6 21.5a11.2 11.2 0 1 0 0 21 9.3 9.3 0 1 1 0-21Z" fill="url(#{{ $uid }}-gold)"/>
    <path d="m40.2 27.4 1.2 2.6 2.8.3-2.1 1.9.6 2.8-2.5-1.4-2.5 1.4.6-2.8-2.1-1.9 2.8-.3Z" fill="#f8eecb"/>
</svg>
