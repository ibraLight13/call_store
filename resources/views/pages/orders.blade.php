@extends('layouts.app')

@section('title', __('commerce.my_orders'))

@section('content')
<section class="page" style="max-width:900px">
    <h1 class="h1">{{ __('commerce.my_orders') }}</h1>
    <p class="sub">{{ __('commerce.orders_sub') }}</p>

    <div class="panel">
        @forelse ($orders as $order)
            <div class="notif">
                <span class="dot">#{{ $order->id }}</span>
                <div style="flex:1">
                    <a href="{{ route('orders.show', $order) }}" style="font-weight:700">
                        {{ __('commerce.order') }} #{{ $order->id }}
                    </a>
                    <span class="badge badge-soft" style="margin-inline-start:6px">{{ __('commerce.state_'.$order->state::getMorphClass()) }}</span>
                    <div class="muted" style="font-size:12.5px;margin-top:2px">
                        {{ $order->items->count() }} {{ __('social.pieces') }} · {{ __('commerce.placed_on') }} {{ $order->placed_at?->translatedFormat('d M Y') }}
                    </div>
                </div>
                <b style="font-family:var(--f-lat)">{{ \App\Support\Money::format($order->total_minor) }} SAR</b>
            </div>
        @empty
            <p class="muted" style="text-align:center;padding:30px 0;margin:0">{{ __('commerce.no_orders') }}</p>
        @endforelse
    </div>

    {{ $orders->links() }}
</section>
@endsection
