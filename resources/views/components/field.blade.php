@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'hint' => null,
    'rows' => 4,
    'options' => null,
])

@php
    $id = 'f-'.str_replace(['[', ']', '.'], '-', $name);
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $current = old($key, $value instanceof \DateTimeInterface ? $value->format($type === 'datetime-local' ? 'Y-m-d\TH:i' : 'Y-m-d') : $value);
    $invalid = $errors->has($key);
    $classes = 'input'.($invalid ? ' input-error' : '');
@endphp

<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-red-500">*</span>@endif</label>
    @endif

    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" class="{{ $classes }}" @required($required) {{ $attributes->except('class') }}>{{ $current }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" class="{{ $classes }}" @required($required) {{ $attributes->except('class') }}>
            @if ($options !== null)
                @foreach ($options as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected((string) $current === (string) $optValue)>{{ $optLabel }}</option>
                @endforeach
            @endif
            {{ $slot }}
        </select>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : $current }}" class="{{ $classes }}" @required($required) {{ $attributes->except('class') }}>
    @endif

    @if ($hint && !$invalid)
        <p class="mt-1.5 text-xs text-stone-500">{{ $hint }}</p>
    @endif
    @error($key)
        <p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>
    @enderror
</div>
