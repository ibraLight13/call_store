@extends('layouts.app')

@section('title', __('social.my_boards'))

@section('content')
<section class="page">
    <h1 class="h1">{{ __('social.my_boards') }}</h1>
    <p class="sub">{{ __('social.boards_sub') }}</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">
        @forelse ($boards as $board)
            <a href="{{ route('boards.show', $board) }}" class="board-card">
                <div class="mark" style="width:46px;height:46px;border-radius:13px;background:linear-gradient(150deg,var(--gold),#7a5310)"></div>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:700">{{ $board->name }}</div>
                    <div class="muted" style="font-size:13px">{{ $board->pieces_count }} {{ __('social.pieces') }} · {{ $board->is_private ? '🔒 '.__('social.private') : '🌐 '.__('social.public') }}</div>
                </div>
            </a>
        @empty
            <div class="empty-state" style="grid-column:1/-1">@include('partials.ink-pattern')
                <div class="art">🔖</div>
                <div>{{ __('social.no_boards_yet') }}</div>
            </div>
        @endforelse
    </div>
</section>
@endsection
