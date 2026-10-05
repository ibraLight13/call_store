{{-- Script ribbon: each script's name set in its own calligraphic face; every tile is a real explore link. --}}
<nav class="ribbon" aria-label="{{ __('shell.f_script') }}">
    <a href="{{ route('explore') }}" class="ribbon-all" @if ($activeId === null) aria-current="true" @endif>{{ __('shell.all') }}</a>
    @foreach ($scripts as $script)
        <a href="{{ route('explore', ['script' => $script->id]) }}"
           @if ($activeId === (int) $script->id) aria-current="true" @endif
           style="font-family:{{ \App\Support\Palette::font($script->slug) }};font-weight:{{ \App\Support\Palette::weight($script->slug) }}">{{ $script->{'name_'.app()->getLocale()} }}</a>
    @endforeach
</nav>
