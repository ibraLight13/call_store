@php
    $cover = $piece->coverMedia();
    $imgUrl = $cover?->derivativeUrl(480);
    $scriptSlug = $piece->script?->slug ?? 'naskh';
    $font = config('catalog.script_fonts')[$scriptSlug] ?? "'Amiri', serif";
    $weight = config('catalog.script_weights')[$scriptSlug] ?? 400;
    $tints = config('catalog.paper_tints');
    [$bg, $ink] = $tints[$index % count($tints)];
@endphp
<article class="pcard">
    <a href="{{ route('pieces.show', $piece) }}" class="pcard-media" aria-label="{{ $piece->text }}">
        @if ($imgUrl)
            <img src="{{ $imgUrl }}" alt="{{ $piece->text }}" loading="lazy"
                 width="480" height="360">
        @else
            <span class="pcard-art" style="background:{{ $bg }};color:{{ $ink }};font-family:{{ $font }};font-weight:{{ $weight }}">
                {{ $piece->text }}
                <span class="pcard-frame" aria-hidden="true"></span>
            </span>
        @endif
        @if ($piece->script)
            <span class="scr-seal">{{ $piece->script->{'name_'.app()->getLocale()} }}</span>
        @endif
        @if ($piece->sold_at)
            <span class="badge badge-gold pcard-sold">{{ __('commerce.sold') }}</span>
        @endif
    </a>

    <div class="pcard-body">
        <a href="{{ route('pieces.show', $piece) }}" class="pcard-title">{{ $piece->text }}</a>

        <div class="pcard-meta">
            <a href="{{ $piece->seller->profileUrl() }}" class="pcard-seller" title="{{ $piece->seller->name }}">
                {{ $piece->seller->name }}
            </a>

            @if ($piece->isReady() && $piece->price_minor !== null && ! $piece->sold_at)
                <span class="pcard-price">{{ \App\Support\Money::format($piece->price_minor) }} <small>SAR</small></span>
            @elseif (! $piece->isReady())
                <span class="badge badge-primary">{{ __('shell.t_commission') }}</span>
            @endif
        </div>
    </div>
</article>
