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
            </div>
        </div>
        <div style="display:flex;gap:10px;padding-bottom:8px;align-items:flex-start">
            @if ($isSelf)
                <a href="{{ route('settings.profile.edit') }}" class="btn btn-outline btn-sm">{{ __('shell.edit_profile') }}</a>
            @else
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
        <div class="empty-state">
            <div class="art">✦</div>
            <div>{{ __('shell.no_pieces_yet') }}</div>
        </div>
    </div>

    <div class="sec-title"><h3>{{ __('shell.tab_about') }}</h3></div>
    <div class="panel">
        @if ($profile->bio)
            <p style="margin:0;white-space:pre-line">{{ $profile->bio }}</p>
        @else
            <p class="muted" style="margin:0">{{ __('shell.no_bio') }}</p>
        @endif
    </div>
</section>
@endsection
