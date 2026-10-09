@php
    $items = [
        ['Profil MUI', 'landmark', 'profilemui'],
        ['Visi & Misi', 'compass', 'visi-misi'],
        ['Struktur Organisasi', 'users', 'struktur-organisasi'],
        ['Kontak', 'phone', 'kontak'],
    ];
@endphp

<nav {{ $attributes->merge(['class' => 'scrollbar-none -mx-4 flex gap-2 overflow-x-auto px-4 pb-2']) }} aria-label="Halaman profil">
    @foreach ($items as [$label, $icon, $route])
        <a href="{{ route($route) }}" @class(['chip shrink-0', 'active' => request()->routeIs($route)]) @if (request()->routeIs($route)) aria-current="page" @endif>
            <x-icon :name="$icon" class="size-4" /> {{ $label }}
        </a>
    @endforeach
</nav>
