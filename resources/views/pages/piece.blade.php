@extends('layouts.app')

@section('title', $piece->text)

@section('content')
@php
    $locale = app()->getLocale();
    $pin = \App\Support\PinPresenter::present($piece, 0);
    $large = $piece->coverMedia()?->derivativeUrl(960) ?? $pin['img'];
    $sold = $piece->sold_at !== null;
    $buyable = $piece->isReady() && $piece->price_minor !== null && ! $sold;
    $isOwner = auth()->check() && (int) auth()->id() === (int) $piece->seller_id;
@endphp
<section class="page">
    <div class="piece">
        <div>
            <div class="frame">
                <span class="frame-art" style="aspect-ratio:4 / 3">
                    @if ($large)
                        <img src="{{ $large }}" alt="{{ $piece->text }}" width="960" height="720"
                             onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                    @endif
                    <span class="typeset" @if ($large) hidden @endif
                          style="background:{{ $pin['bg'] }};color:{{ $pin['ink'] }};font-family:{{ $pin['font'] }};font-weight:{{ $pin['weight'] }};font-size:min({{ $pin['size'] * 2 }}px, 12cqi)">{{ $piece->text }}</span>
                </span>
            </div>

            @if ($piece->media->count() > 1)
                <div class="thumbs">
                    @foreach ($piece->media as $medium)
                        <img src="{{ $medium->derivativeUrl(480) ?? '' }}" alt="" loading="lazy" width="480" height="360">
                    @endforeach
                </div>
            @endif
        </div>

        <div class="piece-info">
            <h1>{{ $piece->text }}</h1>

            <div class="artist">
                <a href="{{ $piece->seller->profileUrl() }}" class="dot" aria-hidden="true" tabindex="-1">{{ mb_substr($piece->seller->name, 0, 1) }}</a>
                <a href="{{ $piece->seller->profileUrl() }}">
                    <span class="n">{{ $piece->seller->name }}</span><br>
                    <span class="u">{{ '@'.$piece->seller->username }}</span>
                </a>
                @unless ($isOwner)
                    @livewire(\App\Livewire\FollowButton::class, ['profile' => $piece->seller], key('follow-'.$piece->seller_id))
                @endunless
            </div>

            @if ($buyable)
                <div class="price">{{ \App\Support\Money::format($piece->price_minor) }} <small>{{ config('catalog.currency') }}</small></div>
            @elseif ($sold)
                <span class="badge badge-gold">{{ __('commerce.sold') }}</span>
            @endif

            <div class="actions">
                @if ($buyable)
                    @auth
                        <form method="POST" action="{{ route('cart.add', $piece) }}">
                            @csrf
                            <button type="submit" class="btn btn-block btn-primary">{{ __('commerce.add_to_cart') }}</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-block btn-primary">{{ __('commerce.add_to_cart') }}</a>
                    @endauth
                @endif
                @unless ($isOwner)
                    <a class="btn btn-block {{ $buyable ? 'btn-outline' : 'btn-primary' }}" href="{{ route('commissions.create', $piece->seller) }}">{{ __('shell.request_similar') }}</a>
                @endunless
            </div>

            <dl class="specs">
                @if ($piece->script)
                    <dt>{{ __('shell.f_script') }}</dt><dd>{{ $piece->script->{'name_'.$locale} }}</dd>
                @endif
                @if ($piece->medium)
                    <dt>{{ __('catalog.medium') }}</dt><dd>{{ $piece->medium->{'name_'.$locale} }}</dd>
                @endif
                @if ($piece->width_cm)
                    <dt>{{ __('catalog.width') }} / {{ __('catalog.height') }}</dt><dd>{{ $piece->width_cm }} × {{ $piece->height_cm }} {{ __('shell.cm') }}</dd>
                @endif
                @if ($piece->theme)
                    <dt>{{ __('shell.f_theme') }}</dt><dd>{{ $piece->theme->{'name_'.$locale} }}</dd>
                @endif
                @if ($piece->transliteration)
                    <dt>{{ __('catalog.transliteration') }}</dt><dd dir="ltr">{{ $piece->transliteration }}</dd>
                @endif
                <dt>{{ __('shell.d_avail') }}</dt><dd>{{ $piece->isReady() ? __('shell.t_ready') : __('shell.t_commission') }}</dd>
            </dl>

            @if ($piece->description)
                <p class="muted" style="white-space:pre-line;margin:18px 0 0">{{ $piece->description }}</p>
            @endif

            <div class="social-row">
                @livewire(\App\Livewire\LikeButton::class, ['piece' => $piece], key('like-'.$piece->id))
                @livewire(\App\Livewire\SaveToBoardButton::class, ['piece' => $piece], key('save-'.$piece->id))
            </div>
        </div>
    </div>

    <div class="sec-title">
        <h3>{{ __('social.comments') }}</h3>
        <span class="badge badge-soft">{{ $topLevel->count() + $repliesByParent->sum(fn ($replies) => $replies->count()) }}</span>
        @auth
            <details style="margin-inline-start:auto">
                <summary class="muted" style="cursor:pointer;font-size:var(--t-xs)">{{ __('reviews.report') }}</summary>
                <form method="POST" action="{{ route('reports.store') }}" style="margin-top:10px;max-width:420px" onsubmit="this.querySelector('button').disabled=true">
                    @csrf
                    <input type="hidden" name="reportable_type" value="piece">
                    <input type="hidden" name="reportable_id" value="{{ $piece->id }}">
                    <select name="reason" class="input" required>
                        @foreach (['inappropriate', 'offensive', 'copyright', 'scam', 'other'] as $reason)
                            <option value="{{ $reason }}">{{ __("reports.reason_$reason") }}</option>
                        @endforeach
                    </select>
                    <textarea name="note" class="input" style="margin-top:8px" maxlength="1000"></textarea>
                    <button type="submit" class="btn btn-outline btn-sm" style="margin-top:8px">{{ __('reviews.report') }}</button>
                </form>
            </details>
        @endauth
    </div>
    <div class="panel">
        @forelse ($topLevel as $comment)
            @include('partials.comment', ['comment' => $comment])
        @empty
            <p class="muted" style="margin:0">{{ __('social.no_comments') }}</p>
        @endforelse

        @auth
            <form method="POST" action="{{ route('comments.store', $piece) }}" style="margin-top:16px">
                @csrf
                <textarea name="body" required minlength="2" maxlength="2000" placeholder="{{ __('social.add_comment') }}" aria-label="{{ __('social.add_comment') }}"></textarea>
                @error('body')<div class="err">{{ $message }}</div>@enderror
                <div style="text-align:end;margin-top:10px">
                    <button type="submit" class="btn btn-dark btn-sm">{{ __('shell.send') }}</button>
                </div>
            </form>
        @else
            <p class="muted" style="margin:16px 0 0;text-align:center">
                <a href="{{ route('login') }}" style="color:var(--primary-ink);font-weight:700">{{ __('shell.login') }}</a> {{ __('social.to_comment') }}
            </p>
        @endauth
    </div>

    <div class="sec-title"><h3>{{ __('shell.related') }}</h3></div>
    @if ($related->isNotEmpty())
        <div class="pin-grid">
            @foreach ($related as $relatedPiece)
                @include('partials.pin-card', ['pin' => \App\Support\PinPresenter::present($relatedPiece, $loop->index)])
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <p class="sub">{{ __('shell.no_results') }}</p>
            <a class="btn btn-dark" href="{{ route('explore') }}">{{ __('shell.nav_explore') }}</a>
        </div>
    @endif
</section>
@endsection
