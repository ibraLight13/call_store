@php
    $tints = config('catalog.paper_tints');
    [$bg, $ink] = $tints[$index % count($tints)];
    $scriptSlug = $piece->script?->slug ?? 'naskh';
    $font = config('catalog.script_fonts')[$scriptSlug] ?? "'Amiri', serif";
    $weight = config('catalog.script_weights')[$scriptSlug] ?? 400;
    $fontSize = max(22, min(54, (int) (220 / max(6, mb_strlen($piece->text)) + 14)));
@endphp
<div class="pin">
    <div class="canvas" style="background:{{ $bg }}">
        <span class="scr-chip">{{ $piece->script?->{'name_'.app()->getLocale()} ?? '' }}</span>
        <div class="art" style="color:{{ $ink }};font-family:{{ $font }};font-weight:{{ $weight }};font-size:{{ $fontSize }}px">{{ $piece->text }}</div>
        <div class="paper-edge"></div>
    </div>
    <div class="meta">
        <div class="who">
            <a href="{{ $piece->seller->profileUrl() }}" class="dot" title="{{ $piece->seller->name }}">{{ mb_substr($piece->seller->name, 0, 1) }}</a>
            <a href="{{ $piece->seller->profileUrl() }}" class="nm">{{ $piece->seller->name }}</a>
        </div>
        @if ($piece->isReady() && $piece->price_minor !== null)
            <span class="pr">{{ \App\Support\Money::format($piece->price_minor) }} <span style="font-size:11px;color:var(--ink-faint)">SAR</span></span>
        @else
            <span class="badge badge-primary" style="font-size:11px">{{ __('shell.t_commission') }}</span>
        @endif
    </div>
</div>
