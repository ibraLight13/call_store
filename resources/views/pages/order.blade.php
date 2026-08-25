@extends('layouts.app')

@section('title', __('commerce.order').' #'.$order->id)

@php
    $stateKey = 'commerce.state_'.$order->state::getMorphClass();
    $isBuyer = auth()->check() && (int) $order->buyer_id === (int) auth()->user()->id;
@endphp

@section('content')
<section class="page" style="max-width:900px">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <h1 class="h1" style="margin:0">{{ __('commerce.order') }} <span style="font-family:var(--f-lat);color:var(--ink-faint)">#{{ $order->id }}</span></h1>
        <span class="badge badge-gold">{{ __($stateKey) }}</span>
    </div>
    @if ($order->placed_at)
        <p class="sub">{{ __('commerce.placed_on') }} {{ $order->placed_at->translatedFormat('d F Y') }}</p>
    @endif

    @if ($order->state::getMorphClass() === 'refunded')
        <div class="panel" style="border-inline-start:4px solid var(--gold);padding:12px 18px;margin-bottom:16px;color:var(--gold);font-weight:600">{{ __('commerce.refunded_note') }}</div>
    @endif

    <div class="grid2" style="margin-top:14px">
        <div>
            @foreach ($order->sellers as $group)
                <div class="panel" style="margin-bottom:16px">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                        <span class="dot" style="width:30px;height:30px">{{ mb_substr($group->seller->name, 0, 1) }}</span>
                        <b>{{ $group->seller->name }}</b>
                        @if ($group->shipped_at)
                            <span class="badge badge-primary" style="margin-inline-start:auto">🚚 {{ __('commerce.shipped_at') }} {{ $group->shipped_at->translatedFormat('d M') }}</span>
                        @else
                            <span class="badge badge-soft" style="margin-inline-start:auto">{{ __('commerce.transfer_status') }}: {{ __('commerce.state_paid') }}</span>
                        @endif
                    </div>

                    @foreach ($group->items()->with('piece')->get() as $item)
                        <div style="display:flex;gap:12px;align-items:center;padding:10px 0;border-top:1px solid var(--line)">
                            <div style="flex:1;font-weight:700">{{ \Illuminate\Support\Str::limit($item->text, 40) }}</div>
                            <div style="font-family:var(--f-lat);font-weight:700">{{ \App\Support\Money::format($item->price_minor) }}</div>
                        </div>
                    @endforeach

                    <div class="kv"><span class="k">{{ __('commerce.subtotal') }}</span><span class="v">{{ \App\Support\Money::format($group->subtotal_minor) }}</span></div>
                    <div class="kv"><span class="k">{{ __('commerce.shipping') }}</span><span class="v">{{ \App\Support\Money::format($group->shipping_minor) }}</span></div>

                    @can('refund', $order)
                        <form method="POST" action="{{ route('orders.refund', $order) }}" onsubmit="return confirm('{{ __('commerce.refund_confirm') }}')" style="margin-top:10px;text-align:end">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm">{{ __('commerce.refund') }}</button>
                        </form>
                    @endcan
                </div>
            @endforeach
        </div>

        <div>
            <div class="panel" style="margin-bottom:16px">
                <div style="font-weight:700;margin-bottom:10px">{{ __('commerce.ship_to') }}</div>
                <div style="font-size:14px;line-height:1.8">
                    <b>{{ $order['address']['recipient_name'] ?? '' }}</b> · {{ $order['address']['phone'] ?? '' }}<br>
                    {{ $order['address']['street'] ?? '' }}@if(!empty($order['address']['building'])) · {{ $order['address']['building'] }} @endif<br>
                    {{ $order['address']['district'] ?? '' }} · {{ $order['address']['city'] ?? '' }} · {{ $order['address']['country'] ?? '' }}
                </div>
            </div>

            <div class="panel">
                <div style="font-weight:700;margin-bottom:12px">{{ __('commerce.summary') }}</div>
                <div class="kv"><span class="k">{{ __('commerce.subtotal') }}</span><span class="v">{{ \App\Support\Money::format($order->subtotal_minor) }}</span></div>
                <div class="kv"><span class="k">{{ __('commerce.shipping') }} × {{ $order->sellers->count() }}</span><span class="v">{{ \App\Support\Money::format($order->shipping_minor) }}</span></div>
                <div class="kv"><span class="k" style="font-weight:700;color:var(--ink)">{{ __('commerce.total') }}</span><span class="v" style="font-size:19px">{{ \App\Support\Money::format($order->total_minor) }} SAR</span></div>
                @if ($isBuyer && $order->state::getMorphClass() === 'pending_payment')
                    <form method="POST" action="{{ route('checkout.fake_pay', $order) }}" style="margin-top:14px">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-block">🔒 {{ __('commerce.pay_now_fake') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
