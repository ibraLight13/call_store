@extends('layouts.app')

@section('title', __('commissions.my_commissions'))

@section('content')
<section class="page" style="max-width:900px">
    <h1 class="h1">{{ __('commissions.my_commissions') }}</h1>
    <p class="sub">{{ __('commissions.commissions_sub') }}</p>

    <div class="panel">
        @forelse ($commissions as $commission)
            @php($iAmBuyer = $actorId === (int) $commission->buyer_id)
            <a href="{{ route('commissions.show', $commission) }}" style="display:flex;gap:12px;align-items:center;padding:12px 6px;border-bottom:1px solid var(--line)">
                <span class="dot" style="width:36px;height:36px">{{ mb_substr($iAmBuyer ? $commission->seller->name : $commission->buyer->name, 0, 1) }}</span>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:700">{{ __('commerce.order') }} {{ $commission->number }} · {{ \Illuminate\Support\Str::limit($commission->text, 34) }}</div>
                    <div class="muted" style="font-size:12.5px">
                        {{ $iAmBuyer ? __('shell.f_script').': '.$commission->seller->name : __('shell.name').': '.$commission->buyer->name }}
                    </div>
                </div>
                @if ($commission->quote_minor)
                    <b style="font-family:var(--f-lat)">{{ \App\Support\Money::format($commission->quote_minor) }} SAR</b>
                @endif
                <span class="badge badge-gold">{{ __('commission_state.'.$commission->state::getMorphClass()) }}</span>
            </a>
        @empty
            <p class="muted" style="text-align:center;padding:30px 0;margin:0">—</p>
        @endforelse
    </div>

    {{ $commissions->links() }}
</section>
@endsection
