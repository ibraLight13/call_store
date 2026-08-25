@extends('layouts.app')

@section('title', $piece->text)

@section('content')
<section class="page">
    <a class="btn btn-ghost btn-sm" href="{{ url()->previous() ?: route('home') }}" style="margin-bottom:16px">← {{ __('shell.back') }}</a>

    <div class="grid2">
        <div>
            <div class="canvas" style="background:#fdf6e9;min-height:440px;border-radius:var(--radius-lg)">
                <div class="art" style="color:#7a2f1e;font-size:52px">{{ $piece->text }}</div>
                <div class="paper-edge"></div>
            </div>

            @php($media = $piece->media)
            @if ($media->isNotEmpty())
                <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap">
                    @foreach ($media as $medium)
                        <img src="{{ $medium->derivativeUrl(480) ?? '' }}" alt=""
                             style="width:110px;height:82px;object-fit:cover;border-radius:var(--radius-sm);box-shadow:var(--shadow-1)"
                             loading="lazy" width="480" height="360">
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap">
                @if ($piece->script)
                    <span class="badge badge-primary">{{ __('shell.f_script') }}: {{ $piece->script->{'name_'.app()->getLocale()} }}</span>
                @endif
                @if ($piece->theme)
                    <span class="badge badge-gold">{{ $piece->theme->{'name_'.app()->getLocale()} }}</span>
                @endif
                <span class="badge badge-soft">{{ __('shell.handmade') }}</span>
            </div>

            <h1 class="h1" style="font-size:26px">{{ $piece->text }}</h1>

            <a href="{{ $piece->seller->profileUrl() }}" style="display:flex;align-items:center;gap:10px;margin:12px 0 18px">
                <span class="dot" style="width:46px;height:46px;font-size:20px">{{ mb_substr($piece->seller->name, 0, 1) }}</span>
                <span><b>{{ $piece->seller->name }}</b><br><span class="muted" style="font-size:13px">{{ '@'.$piece->seller->username }}</span></span>
            </a>

            <div class="panel" style="margin-bottom:16px">
                <div class="kv"><span class="k">{{ __('catalog.text') }}</span><span class="v" style="font-family:var(--f-disp);font-size:17px">{{ $piece->text }}</span></div>
                @if ($piece->transliteration)
                    <div class="kv"><span class="k">{{ __('catalog.transliteration') }}</span><span class="v" dir="ltr">{{ $piece->transliteration }}</span></div>
                @endif
                @if ($piece->medium)
                    <div class="kv"><span class="k">{{ __('catalog.medium') }}</span><span class="v">{{ $piece->medium->{'name_'.app()->getLocale()} }}</span></div>
                @endif
                @if ($piece->width_cm)
                    <div class="kv"><span class="k">{{ __('catalog.width') }} / {{ __('catalog.height') }}</span><span class="v">{{ $piece->width_cm }} × {{ $piece->height_cm }} سم</span></div>
                @endif
                <div class="kv">
                    <span class="k">{{ __('shell.d_avail') }}</span>
                    <span class="v">
                        @if ($piece->isReady())
                            {{ __('shell.t_ready') }}
                        @else
                            {{ __('shell.t_commission') }}
                        @endif
                    </span>
                </div>
            </div>

            @if ($piece->isReady())
                <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:14px">
                    <span style="font-family:var(--f-lat);font-size:32px;font-weight:700">{{ \App\Support\Money::format($piece->price_minor) }}</span>
                    <span class="muted">SAR</span>
                </div>
            @endif

            @if ($piece->description)
                <p class="muted" style="white-space:pre-line;margin-bottom:16px">{{ $piece->description }}</p>
            @endif

            <div class="row" style="margin-bottom:10px">
                <button type="button" class="btn btn-primary btn-block" disabled title="{{ __('shell.soon_title') }}">{{ __('shell.buy_now') }}</button>
            </div>
            <button type="button" class="btn btn-outline btn-block" onclick="location.href='{{ route('explore', ['q' => '', 'script' => $piece->script_id]) }}'" hidden></button>
        </div>
    </div>

    <div class="sec-title"><h3>{{ __('shell.related') }}</h3></div>
    <div class="pin-grid">
        @foreach ($related as $index => $relatedPiece)
            @include('partials.pin-card', ['piece' => $relatedPiece, 'index' => $loop->index])
        @endforeach
    </div>
</section>
@endsection
