{{-- Script marquee: each script's name in its own calligraphic face, scrolling slowly; every tile links into explore.
     The second set only exists to make the loop seamless, so it is hidden from assistive tech and the tab order.
     Pauses on hover/focus, when off-screen, and becomes a plain scrollable row under reduced motion.
     Admins choose which scripts appear here, their order and font (admin → Script styles). --}}
@php
    $locale = app()->getLocale();
    $other = $locale === 'ar' ? 'en' : 'ar';
    $scripts = $scripts->filter(fn ($script) => $script->show_on_home ?? true)->values();
@endphp
@if ($scripts->isNotEmpty())
<nav class="marquee" aria-label="{{ __('shell.f_script') }}">
    <div class="marquee-track">
        @foreach ([false, true] as $copy)
            <div class="marquee-set"{!! $copy ? ' aria-hidden="true"' : '' !!}>
                @foreach ($scripts as $script)
                    <a href="{{ route('explore', ['script' => $script->id]) }}"{!! $copy ? ' tabindex="-1"' : '' !!} style="--i:{{ $loop->index }}">
                        <span class="marquee-name" style="font-family:{{ \App\Support\Palette::font($script->slug, $script->font) }};font-weight:{{ \App\Support\Palette::weight($script->slug, $script->weight) }}">{{ $script->{'name_'.$locale} }}</span>
                        <span class="marquee-sub">{{ $script->{'name_'.$other} }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>
</nav>
@endif
