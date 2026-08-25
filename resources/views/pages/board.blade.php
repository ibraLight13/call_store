@extends('layouts.app')

@section('title', $board->name)

@section('content')
<section class="page">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:8px">
        <div class="mark" style="width:60px;height:60px;border-radius:18px;background:linear-gradient(150deg,var(--gold),#7a5310)"></div>
        <div>
            <h1 class="h1" style="margin:0">{{ $board->name }}</h1>
            <div class="muted">{{ $board->pieces_count }} {{ __('social.pieces') }} · {{ $board->is_private ? '🔒 '.__('social.private') : '🌐 '.__('social.public') }}</div>
        </div>
    </div>

    <div class="pin-grid" style="margin-top:18px">
        @forelse ($pieces as $index => $piece)
            @include('partials.pin-card', ['piece' => $piece, 'index' => $loop->index])
        @empty
            <div class="empty-state" style="grid-column:1/-1">
                <div class="art">🔖</div>
                <div>{{ __('social.board_empty') }}</div>
            </div>
        @endforelse
    </div>
</section>
@endsection
