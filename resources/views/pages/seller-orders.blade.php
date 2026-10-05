@extends('layouts.app')

@section('title', __('commerce.my_orders').' — '.__('shell.nav_dash'))

@section('content')
<section class="page" style="max-width:1000px">
    <h1 class="h1">{{ __('commerce.items_from_seller', ['name' => auth()->user()->name]) }}</h1>
    <p class="sub">{{ __('commerce.orders_sub') }}</p>

    @if (session('status'))
        <div class="panel" style="border-inline-start:4px solid var(--primary);margin-bottom:16px;padding:12px 18px;font-weight:600;color:var(--primary-ink)">✓ {{ session('status') }}</div>
    @endif

    @forelse ($groups as $group)
        <div class="panel" style="margin-bottom:16px">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px">
                <b>{{ __('commerce.order') }} #{{ $group->order_id }}</b>
                <span class="badge badge-soft">{{ __('commerce.state_'.$group->order->state::getMorphClass()) }}</span>
                <span class="muted" style="font-size:12.5px">{{ $group->order->buyer?->name }}</span>
                <span class="badge badge-gold" style="margin-inline-start:auto">
                    {{ __('commerce.transfer_status') }}: {{ __($group->transfer_status === 'transferred' ? 'commerce.state_paid' : 'commerce.state_pending_payment') }}
                </span>
            </div>

            @foreach ($group->items()->with('piece')->get() as $item)
                <div style="display:flex;gap:12px;align-items:center;padding:9px 0;border-top:1px solid var(--line)">
                    <a href="{{ $item->piece ? route('pieces.show', $item->piece) : '#' }}" style="flex:1;font-weight:700">{{ \Illuminate\Support\Str::limit($item->text, 50) }}</a>
                    <b style="font-family:var(--f-lat)">{{ \App\Support\Money::format($item->price_minor) }} SAR</b>
                </div>
            @endforeach

            <form method="POST" action="{{ route('seller.orders.ship', $group) }}" style="display:flex;gap:10px;align-items:center;margin-top:10px;border-top:1px solid var(--line);padding-top:12px;flex-wrap:wrap">
                @csrf
                @if (! $group->shipped_at)
                    <input name="tracking_number" class="input" style="max-width:240px;padding:8px 12px" placeholder="{{ __('commerce.tracking_ph') }}">
                    <button type="submit" class="btn btn-dark btn-sm">🚚 {{ __('commerce.mark_shipped') }}</button>
                @else
                    <span class="badge badge-primary">🚚 {{ __('commerce.shipped_at') }}: {{ $group->shipped_at->translatedFormat('d M Y') }}</span>
                    @if ($group->tracking_number)<span class="muted" style="font-size:13px" dir="ltr">{{ $group->tracking_number }}</span>@endif
                @endif
            </form>
        </div>
    @empty
        <div class="empty-state">@include('partials.ink-pattern')<div class="art">📦</div><div>{{ __('commerce.no_orders') }}</div></div>
    @endforelse

    {{ $groups->links() }}
</section>
@endsection
