@props(['status'])

@php
    $map = [
        'aktif' => ['badge-green', 'circle-check-big', 'Aktif'],
        'direvisi' => ['badge-gold', 'pencil', 'Direvisi'],
        'digantikan' => ['badge-gray', 'history', 'Digantikan'],
    ];
    [$class, $icon, $label] = $map[$status] ?? ['badge-gray', 'info', ucfirst((string) $status)];
@endphp

<span {{ $attributes->merge(['class' => "badge {$class}"]) }}><x-icon :name="$icon" class="size-3" /> {{ $label }}</span>
