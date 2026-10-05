@extends('layouts.app')

@section('title', $profile->name)

@section('content')
<section class="page">
    <div class="cover" @if($profile->cover_path) style="background-image:url('{{ Storage::disk('public')->url($profile->cover_path) }}');background-size:cover;background-position:center" @endif>
        <div class="ghost">خَطّ</div>
    </div>
    <div class="prof-head">
        <div class="prof-av">
            @if ($profile->avatar_path)
                <img src="{{ Storage::disk('public')->url($profile->avatar_path) }}" alt="{{ $profile->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
            @else
                {{ mb_substr($profile->name, 0, 1) }}
            @endif
        </div>
        <div class="prof-meta">
            <h2>{{ $profile->name }}</h2>
            <div class="muted" style="margin-top:4px">{{ '@'.$profile->username }}@if($profile->bio) · {{ Str::limit($profile->bio, 60) }}@endif</div>
            <div class="prof-stats">
                <span><b>{{ $profile->pieces_count ?? 0 }}</b> <span>{{ __('shell.s_pieces') }}</span></span>
                <span><b>{{ number_format($profile->followers_count) }}</b> <span>{{ __('shell.s_followers') }}</span></span>
                <span><b>{{ number_format($profile->following_count) }}</b> <span>{{ __('shell.s_following') }}</span></span>
                @if ($reviewsTotal > 0)
                    <span><b>{{ number_format($avgRating, 1) }}★</b> <span>{{ __('reviews.rating_avg') }} ({{ $reviewsTotal }})</span></span>
                @endif
            </div>
        </div>
        <div style="display:flex;gap:10px;padding-bottom:8px;align-items:flex-start">
            @if ($isSelf)
                <a href="{{ route('settings.profile.edit') }}" class="btn btn-outline btn-sm">{{ __('shell.edit_profile') }}</a>
            @else
                @auth
                    <a href="{{ route('commissions.create', $profile) }}" class="btn btn-gold btn-sm">{{ __('commissions.request_cta') }}</a>
                @endauth
                @livewire(\App\Livewire\FollowButton::class, ['profile' => $profile], key('follow-'.$profile->id))
                @endif
        </div>
    </div>

    <div class="tabs">
        <div class="tab on">{{ __('shell.tab_pieces') }}</div>
        <div class="tab">{{ __('shell.tab_boards') }}</div>
        <div class="tab">{{ __('shell.tab_about') }}</div>
    </div>

    <div class="pin-grid" style="columns:1;margin-bottom:34px">
        <div class="empty-state">@include('partials.ink-pattern')
            <div class="art">✦</div>
            <div>{{ __('shell.no_pieces_yet') }}</div>
        </div>
    <div class="sec-title"><h3>{{ __('reviews.reviews') }}</h3><span class="badge badge-gold">{{ $reviewsTotal }}</span></div>
    <div class="panel">
        @forelse ($reviews as $review)
            <div class="cmt">
                <span class="dot" style="width:38px;height:38px">{{ mb_substr($review->buyer->name, 0, 1) }}</span>
                <div class="body">
                    <div class="nm">{{ $review->buyer->name }} <span class="badge badge-primary" style="font-size:10px">{{ __('reviews.verified_buyer') }}</span></div>
                    <div class="tx" style="color:var(--gold);letter-spacing:2px">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>
                    @if ($review->body)<div class="tx">{{ $review->body }}</div>@endif
                    <div class="mut">{{ $review->created_at->diffForHumans() }}</div>
                </div>
            </div>
        @empty
            <p class="muted" style="margin:0;text-align:center;padding:14px 0">{{ __('reviews.no_reviews') }}</p>
        @endforelse
    </div>

    @if (! $isSelf)
        <details class="panel" style="margin-top:14px">
            <summary class="muted" style="cursor:pointer;font-size:13.5px">🚩 {{ __('reviews.report') }}</summary>
            <form method="POST" action="{{ route('reports.store') }}" style="margin-top:12px">
                @csrf
                <input type="hidden" name="reportable_type" value="user">
                <input type="hidden" name="reportable_id" value="{{ $profile->id }}">
                <select name="reason" class="input" required>
                    @foreach (['inappropriate', 'offensive', 'copyright', 'scam', 'other'] as $reason)
                        <option value="{{ $reason }}">{{ __("reports.reason_$reason") }}</option>
                    @endforeach
                </select>
                <textarea name="note" class="input" style="margin-top:8px" maxlength="1000"></textarea>
                <button type="submit" class="btn btn-outline btn-sm" style="margin-top:8px">{{ __('reviews.report') }}</button>
            </form>
        </details>
    @endif
</section>
@endsection
