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
                 width="480" height="360" style="object-fit:cover;width:100%;height:100%;display:block">
        @else
            <span class="pcard-art" style="background:{{ $bg }};color:{{ $ink }};font-family:{{ $font }};font-weight:{{ $weight }}">
                {{ $piece->text }}
            </span>
            <span class="paper-edge"></span>
        @endif
        @if ($piece->script)
            <span class="scr-chip">{{ $piece->script->{'name_'.app()->getLocale()} }}</span>
        @endif
        @if ($piece->sold_at)
            <span class="badge badge-gold pcard-sold">{{ __('commerce.sold') }}</span>
        @endif
    </a>

    <div class="pcard-body">
        <a href="{{ route('pieces.show', $piece) }}" class="pcard-title">{{ $piece->text }}</a>

        <div class="pcard-meta">
            <a href="{{ $piece->seller->profileUrl() }}" class="pcard-seller">
                <span class="dot" style="width:22px;height:22px;font-size:10px">{{ mb_substr($piece->seller->name, 0, 1) }}</span>
                <span class="nm">{{ $piece->seller->name }}</span>
            </a>

            @if ($piece->isReady() && $piece->price_minor !== null && ! $piece->sold_at)
                <span class="pcard-price">{{ \App\Support\Money::format($piece->price_minor) }} <small>SAR</small></span>
            @elseif (! $piece->isReady())
                <span class="badge badge-primary" style="font-size:10.5px">{{ __('shell.t_commission') }}</span>
            @endif
        </div>
    </div>
</article>
