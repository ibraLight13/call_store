@extends('layouts.app')

@section('title', __('commerce.cart'))

@section('content')
<section class="page">
    <h1 class="h1">{{ __('commerce.cart') }}</h1>
    <p class="sub">{{ __('commerce.cart_sub') }}</p>

    @if (session('status'))
        <div class="panel" style="border-inline-start:4px solid var(--primary);margin-bottom:16px;padding:12px 18px;font-weight:600;color:var(--primary-ink)">✓ {{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="panel" style="border-inline-start:4px solid var(--danger);margin-bottom:16px;padding:12px 18px;font-weight:600;color:var(--danger)">{{ session('error') }}</div>
    @endif

    <div class="grid2">
        <div>
            @forelse ($cart['groups'] as $group)
                <div class="panel" style="margin-bottom:16px">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                        <span class="dot" style="width:30px;height:30px">{{ mb_substr($group['seller']->name, 0, 1) }}</span>
                        <a href="{{ $group['seller']->profileUrl() }}"><b>{{ $group['seller']->name }}</b></a>
                        <span class="badge badge-soft" style="margin-inline-start:auto">🚚 {{ \App\Support\Money::format($group['shipping_minor']) }} SAR</span>
                    </div>
                    @foreach ($group['pieces'] as $piece)
                        <div style="display:flex;gap:14px;align-items:center;padding:10px 0;border-top:1px solid var(--line)">
                            <a href="{{ route('pieces.show', $piece) }}" class="canvas" style="background:#fdf6e9;width:72px;height:72px;min-height:0;padding:8px;flex-shrink:0">
                                <div class="art" style="color:#7a2f1e;font-size:13px">{{ \Illuminate\Support\Str::limit($piece->text, 22) }}</div>
                            </a>
                            <div style="flex:1;min-width:0">
                                <a href="{{ route('pieces.show', $piece) }}" style="font-weight:700">{{ $piece->text }}</a>
                                <div class="muted" style="font-size:13px">
                                    {{ $piece->script?->{'name_'.app()->getLocale()} ?? '' }}@if($piece->width_cm) · {{ $piece->width_cm }}×{{ $piece->height_cm }} سم @endif
                                </div>
                            </div>
                            <div style="font-family:var(--f-lat);font-weight:700">{{ \App\Support\Money::format($piece->price_minor) }}</div>
                            <form method="POST" action="{{ route('cart.remove', $piece) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="err" style="background:none;border:none;cursor:pointer;font-weight:700" title="{{ __('social.delete') }}">✕</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="empty-state">
                    <div class="art">🛒</div>
                    <div style="font-weight:700;font-size:16px;margin-bottom:4px">{{ __('commerce.empty_cart') }}</div>
                    <a href="{{ route('explore') }}" class="btn btn-dark btn-sm" style="margin-top:10px">{{ __('shell.nav_explore') }}</a>
                </div>
            @endforelse
        </div>

        @if ($cart['groups']->isNotEmpty())
            <div>
                <div class="panel">
                    <div style="font-weight:700;margin-bottom:12px">{{ __('commerce.summary') }}</div>
                    <div class="kv"><span class="k">{{ __('commerce.subtotal') }}</span><span class="v">{{ \App\Support\Money::format($cart['subtotal_minor']) }}</span></div>
                    <div class="kv"><span class="k">{{ __('commerce.shipping') }} {{ __('commerce.per_seller') }} × {{ $cart['groups']->count() }}</span><span class="v">{{ \App\Support\Money::format($cart['shipping_minor']) }}</span></div>
                    <div class="kv"><span class="k" style="font-weight:700;color:var(--ink)">{{ __('commerce.total') }}</span><span class="v" style="font-size:19px">{{ \App\Support\Money::format($cart['total_minor']) }} SAR</span></div>
                    <form action="{{ route('checkout.create') }}" method="GET" style="margin-top:16px">
                        <button type="submit" class="btn btn-primary btn-block">{{ __('commerce.checkout') }}</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
