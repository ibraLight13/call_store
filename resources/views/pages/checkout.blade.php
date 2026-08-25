@extends('layouts.app')

@section('title', __('commerce.checkout'))

@section('content')
<section class="page">
    <h1 class="h1">{{ __('commerce.checkout') }}</h1>
    <p class="sub">{{ __('commerce.shipping_to') }}</p>

    @if (session('error'))
        <div class="panel" style="border-inline-start:4px solid var(--danger);margin-bottom:16px;padding:12px 18px;font-weight:600;color:var(--danger)">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('checkout.store') }}" class="grid2">
        @csrf
        <div>
            <div class="panel">
                <div style="font-weight:700;margin-bottom:12px">{{ __('commerce.shipping_to') }}</div>

                @foreach ($addresses as $address)
                    <label style="display:block;padding:12px;border:1.5px solid var(--line);border-radius:12px;margin-bottom:8px;cursor:pointer;{{ old('address_id') == $address->id ? 'border-color:var(--primary)' : '' }}">
                        <input type="radio" name="address_id" value="{{ $address->id }}" {{ old('address_id') == $address->id ? 'checked' : '' }}>
                        <b>{{ $address->recipient_name }}</b> · {{ $address->phone }}
                        <div class="muted" style="font-size:13px">
                            {{ $address->street }}@if($address->district) · {{ $address->district }} @endif · {{ $address->city }} @if($address->postal_code) · {{ $address->postal_code }} @endif
                        </div>
                    </label>
                @endforeach

                <details {{ $addresses->isEmpty() ? 'open' : '' }}>
                    <summary style="font-weight:700;cursor:pointer;margin-bottom:10px">{{ __('commerce.new_address') }}</summary>
                    <div class="row">
                        <div class="field"><label>{{ __('commerce.recipient_name') }}</label><input name="recipient_name" class="input" value="{{ old('recipient_name') }}"></div>
                        <div class="field"><label>{{ __('commerce.phone') }}</label><input name="phone" class="input" value="{{ old('phone') }}"></div>
                    </div>
                    <div class="row">
                        <div class="field"><label>{{ __('commerce.city') }}</label><input name="city" class="input" value="{{ old('city') }}"></div>
                        <div class="field"><label>{{ __('commerce.district') }}</label><input name="district" class="input" value="{{ old('district') }}"></div>
                    </div>
                    <div class="field"><label>{{ __('commerce.street') }}</label><input name="street" class="input" value="{{ old('street') }}"></div>
                    <div class="row">
                        <div class="field"><label>{{ __('commerce.building') }}</label><input name="building" class="input" value="{{ old('building') }}"></div>
                        <div class="field"><label>{{ __('commerce.postal_code') }}</label><input name="postal_code" class="input" value="{{ old('postal_code') }}"></div>
                    </div>
                    <label style="display:flex;gap:8px;font-weight:400;font-size:13.5px">
                        <input type="checkbox" name="save_address" value="1"> {{ __('commerce.save_address') }}
                    </label>
                </details>
            </div>
        </div>

        <div>
            <div class="panel">
                <div style="font-weight:700;margin-bottom:12px">{{ __('commerce.summary') }}</div>
                @foreach ($cart['groups'] as $group)
                    <div class="kv"><span class="k">{{ __('commerce.items_from_seller', ['name' => $group['seller']->name]) }}</span><span class="v">{{ \App\Support\Money::format($group['subtotal_minor'] + $group['shipping_minor']) }}</span></div>
                @endforeach
                <div class="kv"><span class="k">{{ __('commerce.subtotal') }}</span><span class="v">{{ \App\Support\Money::format($cart['subtotal_minor']) }}</span></div>
                <div class="kv"><span class="k">{{ __('commerce.shipping') }}</span><span class="v">{{ \App\Support\Money::format($cart['shipping_minor']) }}</span></div>
                <div class="kv"><span class="k" style="font-weight:700;color:var(--ink)">{{ __('commerce.total') }}</span><span class="v" style="font-size:19px">{{ \App\Support\Money::format($cart['total_minor']) }} SAR</span></div>
                <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px">🔒 {{ __('commerce.pay_now_fake') }}</button>
            </div>
        </div>
    </form>
</section>
@endsection
