@extends('layouts.app')

@section('title', $filters->q !== null ? __('shell.exp_for').' «'.$filters->q.'»' : __('shell.nav_explore'))

@section('content')
<section class="page">
    <h1 class="h1">
        @if ($filters->q !== null)
            <span>{{ __('shell.exp_for') }}</span> «{{ $filters->q }}»
        @else
            {{ __('shell.nav_explore') }}
        @endif
    </h1>
    <p class="sub">{{ number_format($paginator->count()) }}+ {{ __('shell.exp_results') }}</p>

    <form method="GET" action="{{ route('explore') }}">
        @foreach ($filters->toQueryParams() as $key => $value)
            @if (! in_array($key, ['script', 'theme', 'medium', 'color', 'type', 'price']))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <div class="filterbar">
            <span class="fl">{{ __('shell.f_script') }}</span>
            <a href="{{ route('explore', array_diff_key($filters->toQueryParams(), ['script' => 1])) }}"
               class="pill {{ $filters->scriptId === null ? 'on' : '' }}">{{ __('shell.all') }}</a>
            @foreach ($taxonomy['scripts'] as $script)
                <a href="{{ route('explore', array_merge($filters->toQueryParams(), ['script' => $script->id])) }}"
                   class="pill {{ $filters->scriptId === (int) $script->id ? 'on' : '' }}">{{ $script->{'name_'.app()->getLocale()} }}</a>
            @endforeach
        </div>

        <div class="filterbar">
            <span class="fl">{{ __('shell.f_theme') }}</span>
            @foreach ($taxonomy['themes'] as $theme)
                <a href="{{ route('explore', array_merge(array_diff_key($filters->toQueryParams(), ['theme' => 1]), $filters->themeId === (int) $theme->id ? [] : ['theme' => $theme->id])) }}"
                   class="pill {{ $filters->themeId === (int) $theme->id ? 'on' : '' }}">{{ $theme->{'name_'.app()->getLocale()} }}</a>
            @endforeach

            <span style="width:1px;height:26px;background:var(--line);margin:0 4px"></span>
            <span class="fl">{{ __('shell.f_type') }}</span>
            <a href="{{ route('explore', array_merge(array_diff_key($filters->toQueryParams(), ['type' => 1]), $filters->type === 'ready' ? [] : ['type' => 'ready'])) }}"
               class="pill {{ $filters->type === 'ready' ? 'on' : '' }}">{{ __('shell.t_ready') }}</a>
            <a href="{{ route('explore', array_merge(array_diff_key($filters->toQueryParams(), ['type' => 1]), $filters->type === 'commission' ? [] : ['type' => 'commission'])) }}"
               class="pill {{ $filters->type === 'commission' ? 'on' : '' }}">{{ __('shell.t_commission') }}</a>
        </div>

        <div class="filterbar">
            <span class="fl">{{ __('shell.f_color') }}</span>
            @foreach ($taxonomy['colors'] as $color)
                <a href="{{ route('explore', array_merge(array_diff_key($filters->toQueryParams(), ['color' => 1]), $filters->colorId === (int) $color->id ? [] : ['color' => $color->id])) }}">
                    <span class="swatch" style="background:{{ $color->hex }};{{ $filters->colorId === (int) $color->id ? 'box-shadow:0 0 0 2.5px var(--primary)' : '' }}"></span>
                </a>
            @endforeach

            <span style="width:1px;height:26px;background:var(--line);margin:0 6px"></span>
            <span class="fl">{{ __('shell.f_price') }}</span>
            @foreach (['lt500' => '‹ ٥٠٠', '500to1500' => '٥٠٠–١٥٠٠', 'gt1500' => '١٥٠٠ ›'] as $band => $label)
                <a href="{{ route('explore', array_merge(array_diff_key($filters->toQueryParams(), ['price' => 1]), $filters->priceBand === $band ? [] : ['price' => $band])) }}"
                   class="pill {{ $filters->priceBand === $band ? 'on' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </form>

    <div class="pin-grid">
        @forelse ($paginator as $piece)
            @include('partials.pin-card', ['piece' => $piece, 'index' => $loop->index])
        @empty
            <div class="empty-state">
                <div class="art">…</div>
                <div style="font-weight:700;font-size:16px;margin-bottom:4px">{{ __('shell.no_results') }}</div>
                <div>{{ __('shell.try_other_filters') }}</div>
            </div>
        @endforelse
    </div>

    @if ($paginator->hasMorePages())
        <div style="text-align:center;margin-top:26px">
            <a class="btn btn-outline"
               href="{{ request()->fullUrlWithQuery(['cursor' => $paginator->nextCursor()?->encode()]) }}">
                {{ __('shell.load_more') }}
            </a>
        </div>
    @endif
</section>
@endsection
