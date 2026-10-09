@props(['light' => false, 'compact' => false])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <img src="{{ $site['logo_url'] }}" alt="Logo Majelis Ulama Indonesia" width="48" height="48"
         class="size-11 shrink-0 rounded-full bg-white object-contain p-0.5 shadow-sm ring-1 ring-gold-300/70 sm:size-12">
    @unless ($compact)
        <span class="flex flex-col leading-tight">
            <span @class([
                'font-display text-[17px] font-semibold tracking-tight sm:text-[19px]',
                'text-white' => $light,
                'text-brand-900' => !$light,
            ])>{{ $site['site_name'] }}</span>
            <span @class([
                'text-[10.5px] font-bold tracking-[.22em] uppercase sm:text-[11px]',
                'text-gold-300' => $light,
                'text-gold-600' => !$light,
            ])>{{ $site['site_region'] }}</span>
        </span>
    @endunless
</span>
