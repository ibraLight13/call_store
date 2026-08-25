@extends('layouts.app')

@section('title', __('social.notifications'))

@section('content')
<section class="page" style="max-width:820px">
    <div style="display:flex;align-items:center;gap:12px">
        <h1 class="h1" style="margin-bottom:0">{{ __('social.notifications') }}</h1>
        <span class="badge badge-gold">{{ $unreadCount }}</span>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read_all') }}" style="margin-inline-start:auto">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm">{{ __('social.mark_read') }}</button>
            </form>
        @endif
    </div>

    <p class="sub">{{ __('shell.soon_sub') }}</p>

    <div class="panel">
        @forelse ($notifications as $notification)
            @php($data = $notification->data)
            <div class="notif {{ $notification->read_at === null ? 'unread' : '' }}">
                <span class="dot">{{ mb_substr((string) $data['actor_name'], 0, 1) }}</span>
                <div style="flex:1">
                    <div style="font-weight:600;font-size:14px">
                        @if ($data['type'] === 'follow')
                            {{ __('social.notif_follow_line', ['name' => $data['actor_name']]) }}
                            — <a href="{{ url('/@'.$data['username']) }}" style="color:var(--primary-ink)">{{ '@'.$data['username'] }}</a>
                        @else
                            {{ ($data['context'] ?? 'piece') === 'reply' ? __('social.notif_reply_line', ['name' => $data['actor_name']]) : __('social.notif_comment_line', ['name' => $data['actor_name']]) }}
                            — <a href="{{ route('pieces.show', ['piece' => $data['piece_id']]) }}" style="color:var(--primary-ink)">{{ __('social.view_piece') }}</a>
                        @endif
                    </div>
                    <div class="muted" style="font-size:12.5px;margin-top:2px">
                        {{ \Illuminate\Support\Str::limit((string) ($data['excerpt'] ?? ''), 90) }}
                    </div>
                </div>
                <span class="muted" style="font-size:11.5px;flex-shrink:0">{{ \Illuminate\Support\Carbon::parse($notification->created_at)->diffForHumans() }}</span>
            </div>
        @empty
            <p class="muted" style="text-align:center;padding:30px 0;margin:0">{{ __('social.no_notifications') }}</p>
        @endforelse
    </div>

    {{ $notifications->links() }}
</section>
@endsection
