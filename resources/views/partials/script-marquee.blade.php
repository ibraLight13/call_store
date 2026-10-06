{{-- Script marquee: each script's name in its own calligraphic face, scrolling slowly; every tile links into explore.
     The second set only exists to make the loop seamless, so it is hidden from assistive tech and the tab order.
     Pauses on hover/focus, when off-screen, and becomes a plain scrollable row under reduced motion. --}}
<nav class="marquee" aria-label="{{ __('shell.f_script') }}">
    <div class="marquee-track">
        @foreach ([false, true] as $copy)
            <div class="marquee-set"{!! $copy ? ' aria-hidden="true"' : '' !!}>
                @foreach ($scripts as $script)
                    <a href="{{ route('explore', ['script' => $script->id]) }}"{!! $copy ? ' tabindex="-1"' : '' !!}
                       style="font-family:{{ \App\Support\Palette::font($script->slug) }};font-weight:{{ \App\Support\Palette::weight($script->slug) }}">{{ $script->{'name_'.app()->getLocale()} }}</a>
                @endforeach
            </div>
        @endforeach
    </div>
</nav>
