@extends('layouts.app')

@section('title', __('commissions.my_commissions').' '.$commission->number)

@php($stateKey = $stateKey)
@php($quoteText = $commission->quote_minor !== null ? \App\Support\Money::format($commission->quote_minor) : null)

@section('content')
<section class="page">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:6px">
        <h1 class="h1" style="margin:0">{{ __('commerce.order') }} <span style="font-family:var(--f-lat);color:var(--ink-faint)">{{ $commission->number }}</span></h1>
        <span class="badge badge-gold">{{ __('commission_state.'.$stateKey) }}</span>
    </div>
    <p class="sub">
        {{ __('commissions.between') }}
        <a href="{{ $commission->buyer->profileUrl() }}"><b>{{ $commission->buyer->name }}</b></a> ·
        <a href="{{ $commission->seller->profileUrl() }}"><b>{{ $commission->seller->name }}</b></a>
    </p>

    @if (session('status'))<div class="panel" style="border-inline-start:4px solid var(--primary);padding:12px 18px;margin-bottom:16px;font-weight:600;color:var(--primary-ink)">✓ {{ session('status') }}</div>@endif
    @if (session('error'))<div class="panel" style="border-inline-start:4px solid var(--danger);padding:12px 18px;margin-bottom:16px;font-weight:600;color:var(--danger)">{{ session('error') }}</div>@endif

    <div class="grid2">
        <div>
            <div class="panel" style="margin-bottom:16px">
                <div style="font-weight:700;margin-bottom:12px">📋 {{ __('commissions.brief') }}</div>
                <div class="kv"><span class="k">{{ __('catalog.text') }}</span><span class="v" style="font-family:var(--f-disp);font-size:18px">{{ $commission->text }}</span></div>
                @if ($commission->script)<div class="kv"><span class="k">{{ __('catalog.script') }}</span><span class="v">{{ $commission->script->{'name_'.app()->getLocale()} }}</span></div>@endif
                @if ($commission->medium)<div class="kv"><span class="k">{{ __('catalog.medium') }}</span><span class="v">{{ $commission->medium->{'name_'.app()->getLocale()} }}</span></div>@endif
                @if ($commission->width_cm)<div class="kv"><span class="k">{{ __('catalog.width') }} / {{ __('catalog.height') }}</span><span class="v">{{ $commission->width_cm }} × {{ $commission->height_cm }} سم</span></div>@endif
                @if ($commission->deadline)<div class="kv"><span class="k">{{ __('commissions.deadline') }}</span><span class="v">{{ $commission->deadline->translatedFormat('d F Y') }}</span></div>@endif
            </div>

            @php($latestProof)
            @if ($latestProof)
                <div class="panel" style="margin-bottom:16px">
                    <div style="display:flex;align-items:center;margin-bottom:14px">
                        <span style="font-weight:700">🖼️ {{ __('commissions.proof_v', ['version' => $latestProof->version]) }}</span>
                        @if ($stateKey === 'proof_submitted')
                            <span class="badge badge-gold" style="margin-inline-start:auto">{{ __('commissions.awaiting_you') }}</span>
                        @endif
                    </div>

                    <a href="{{ route('commissions.proof', [$commission, $latestProof]) }}" target="_blank">
                        <img src="{{ route('commissions.proof', [$commission, $latestProof]) }}" alt="" loading="lazy"
                             style="width:100%;border-radius:var(--radius);box-shadow:var(--shadow-1)">
                    </a>

                    @if ($latestProof->seller_note)
                        <p class="muted" style="font-size:13.5px;margin-top:12px">💬 {{ __('commissions.artist_note') }} «{{ $latestProof->seller_note }}»</p>
                    @endif

                    @if ($isBuyer && $stateKey === 'proof_submitted')
                        <div class="row" style="margin-top:14px">
                            <form method="POST" action="{{ route('commissions.approve', $commission) }}" style="flex:1" onsubmit="return true">
                                @csrf
                                <button type="submit" class="btn btn-gold btn-block">✔ {{ __('commissions.approve_proof') }}</button>
                            </form>
                            <button type="button" class="btn btn-outline btn-block" onclick="var f=document.getElementById('rev-form');f.style.display=f.style.display==='none'?'block':'none'">{{ __('commissions.request_change') }}</button>
                        </div>
                        <form id="rev-form" method="POST" action="{{ route('commissions.revision', $commission) }}" style="display:none;margin-top:10px">
                            @csrf
                            <textarea name="revision_note" required placeholder="{{ __('commissions.revision_ph') }}"></textarea>
                            <div style="text-align:end;margin-top:8px"><button type="submit" class="btn btn-dark btn-sm">{{ __('social.reply') }}</button></div>
                        </form>
                    @endif
                </div>
            @endif

            <div class="panel">
                <div style="font-weight:700;margin-bottom:12px">💬 {{ __('social.comments') }}</div>
                <div class="thread">
                    @forelse ($commission->messages as $msg)
                        <div class="msg {{ $msg->user_id === auth()->id() ? 'me' : 'them' }}">
                            <div class="mn">{{ $msg->user->name }} · {{ $msg->created_at->diffForHumans() }}</div>
                            {{ $msg->body }}
                        </div>
                    @empty
                        <p class="muted" style="text-align:center;margin:0">{{ __('social.no_comments') }}</p>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('commissions.message', $commission) }}" style="display:flex;gap:10px;margin-top:14px">
                    @csrf
                    <input name="body" class="input" placeholder="{{ __('commissions.write_msg') }}" required maxlength="2000">
                    <button type="submit" class="btn btn-dark btn-sm">{{ __('shell.send') ?? 'إرسال' }}</button>
                </form>
            </div>
        </div>

        <div>
            <div class="panel" style="margin-bottom:16px">
                <div style="font-weight:700;margin-bottom:14px">{{ __('commissions.stages') }}</div>
                <div class="tl">
                    @foreach ([
                        1 => __('commissions.stage_requested'),
                        2 => __('commissions.stage_quoted'),
                        3 => __('commissions.stage_deposit'),
                        4 => __('commissions.stage_work'),
                        5 => __('commissions.stage_proof'),
                        6 => __('commissions.stage_approve'),
                        7 => __('commissions.stage_balance'),
                        8 => __('commissions.stage_ship'),
                        9 => __('commissions.stage_complete'),
                    ] as $step => $label)
                        <div class="tl-item {{ $currentStep > $step ? 'done' : '' }} {{ $currentStep === $step ? 'now' : '' }}">
                            <div class="t">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="panel">
                <div style="font-weight:700;margin-bottom:12px">💳 {{ __('commissions.payment') }}</div>
                <div class="kv"><span class="k">{{ __('commissions.agreed_total') }}</span><span class="v">{{ $quoteText ? $quoteText.' SAR' : '—' }}</span></div>
                <div class="kv">
                    <span class="k">{{ __('commissions.deposit_30') }}</span>
                    <span class="v" style="{{ $commission->deposit_paid_at ? 'color:var(--primary)' : '' }}">
                        {{ $commission->deposit_minor ? \App\Support\Money::format($commission->deposit_minor).' '.($commission->deposit_paid_at ? '✓' : '') : '—' }}
                    </span>
                </div>
                <div class="kv"><span class="k">{{ __('commissions.remaining') }}</span><span class="v">{{ $commission->quote_minor ? \App\Support\Money::format(max(0, $commission->quote_minor - ($commission->deposit_minor ?? 0))) : '—' }}</span></div>

                @if ($isBuyer)
                    @if ($stateKey === 'quoted')
                        <form method="POST" action="{{ route('commissions.fake_pay', ['commission' => $commission, 'purpose' => 'deposit']) }}" style="margin-top:14px">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-block">🔒 {{ __('commissions.pay_deposit_fake') }}</button>
                        </form>
                        <form method="POST" action="{{ route('commissions.cancel', $commission) }}" onsubmit="return confirm('{{ __('commissions.cancel_confirm') }}')" style="margin-top:8px;text-align:center">
                            @csrf
                            <button type="submit" class="err" style="background:none;border:none;cursor:pointer;font-size:13px;font-weight:600">{{ __('commissions.cancel_request') }}</button>
                        </form>
                    @elseif ($stateKey === 'approved')
                        <form method="POST" action="{{ route('commissions.balance', $commission) }}" style="margin-top:14px">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-block">🔒 {{ __('commissions.pay_balance_fake') }}</button>
                        </form>
                    @elseif ($stateKey === 'shipped')
                        <form method="POST" action="{{ route('commissions.complete', $commission) }}" style="margin-top:14px">
                            @csrf
                            <button type="submit" class="btn btn-gold btn-block">✔ {{ __('commissions.confirm_receipt') }}</button>
                        </form>
                    @endif
                @endif

                @role('seller')
                    @if ((int) $commission->seller_id === (int) auth()->id())
                        @if ($stateKey === 'requested')
                            <form method="POST" action="{{ route('commissions.quote', $commission) }}" style="margin-top:14px;border-top:1px solid var(--line);padding-top:14px">
                                @csrf
                                <label style="font-size:13px;font-weight:700;display:block;margin-bottom:6px">{{ __('commissions.your_quote') }}</label>
                                <input type="number" step="1" min="50" name="quote" class="input" required value="{{ old('quote') }}">
                                <textarea name="note" class="input" style="margin-top:8px" placeholder="{{ __('commissions.quote_note_ph') }}">{{ old('note') }}</textarea>
                                <div class="row" style="margin-top:8px">
                                    <button type="submit" class="btn btn-primary btn-sm btn-block">{{ __('commissions.send_quote') }}</button>
                                    <button type="submit" formaction="{{ route('commissions.decline', $commission) }}" formmethod="POST" class="btn btn-outline btn-sm btn-block">@csrf {{ __('commissions.decline_short') }}</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('commissions.decline', $commission) }}" style="display:none">
                                @csrf
                                <input type="hidden" name="reason" value="declined">
                            </form>
                        @elseif ($stateKey === 'deposit_paid')
                            <form method="POST" action="{{ route('commissions.start', $commission) }}" style="margin-top:14px">
                                @csrf
                                <button type="submit" class="btn btn-dark btn-block">🎨 {{ __('commissions.start_work') }}</button>
                            </form>
                        @elseif (in_array($stateKey, ['in_progress', 'revision_requested']))
                            <form method="POST" action="{{ route('commissions.submit_proof', $commission) }}" enctype="multipart/form-data" style="margin-top:14px;border-top:1px solid var(--line);padding-top:12px">
                                @csrf
                                <label style="font-size:13px;font-weight:700;display:block;margin-bottom:6px">{{ __('commissions.upload_proof') }}</label>
                                <input type="file" name="proof_image" accept="image/*" class="input" required>
                                <textarea name="seller_note" class="input" style="margin-top:8px" placeholder="{{ __('commissions.seller_note_ph') }}">{{ old('seller_note') }}</textarea>
                                <button type="submit" class="btn btn-dark btn-block" style="margin-top:8px">{{ __('commissions.submit_proof_btn') }}</button>
                            </form>
                        @elseif ($stateKey === 'balance_paid')
                            <form method="POST" action="{{ route('commissions.ship', $commission) }}" style="margin-top:14px">
                                @csrf
                                <input name="tracking_number" class="input" placeholder="{{ __('commerce.tracking_ph') }}" style="margin-bottom:8px">
                                <button type="submit" class="btn btn-primary btn-block">🚚 {{ __('commerce.mark_shipped') }}</button>
                            </form>
                        @endif
                    @endif
                @endrole
            </div>
        </div>
    </div>
</section>
@endsection
