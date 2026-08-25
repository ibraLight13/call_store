@extends('layouts.app')

@section('title', __('commissions.my_commissions').' — '.$seller->name)

@section('content')
<section class="page">
    <a class="btn btn-ghost btn-sm" href="{{ url()->previous() }}" style="margin-bottom:14px">← {{ __('shell.back') }}</a>
    <div class="eyebrow">{{ __('commissions.my_commissions') }}</div>
    <h1 class="h1">{{ __('commissions.request_title', ['name' => $seller->name]) }}</h1>

    <div class="grid2">
        <div class="panel">
            <form method="POST" action="{{ route('commissions.request', $seller) }}">
                @csrf
                <div class="field">
                    <label>{{ __('catalog.text') }} *</label>
                    <input name="text" class="input arabic-input" value="{{ old('text') }}" required maxlength="500">
                    @error('text')<div class="err">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="field">
                        <label>{{ __('catalog.script') }}</label>
                        <select name="script_id" class="input">
                            <option value="">{{ __('shell.all') }}</option>
                            @foreach ($taxonomy['scripts'] as $script)
                                <option value="{{ $script->id }}" {{ old('script_id') == $script->id ? 'selected' : '' }}>{{ $script->{'name_'.app()->getLocale()} }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>{{ __('catalog.medium') }}</label>
                        <select name="medium_id" class="input">
                            <option value="">—</option>
                            @foreach ($taxonomy['mediums'] as $medium)
                                <option value="{{ $medium->id }}" {{ old('medium_id') == $medium->id ? 'selected' : '' }}>{{ $medium->{'name_'.app()->getLocale()} }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="field"><label>{{ __('catalog.width') }}</label><input type="number" min="5" max="500" name="width_cm" class="input" value="{{ old('width_cm') }}"></div>
                    <div class="field"><label>{{ __('catalog.height') }}</label><input type="number" min="5" max="500" name="height_cm" class="input" value="{{ old('height_cm') }}"></div>
                </div>

                <div class="row">
                    <div class="field"><label>{{ __('commissions.budget') }}</label><input type="number" step="0.01" min="0" name="budget" class="input" value="{{ old('budget') }}"></div>
                    <div class="field"><label>{{ __('commissions.deadline') }}</label><input type="date" name="deadline" class="input" value="{{ old('deadline') }}" min="{{ now()->toDateString() }}"></div>
                </div>

                <div class="field">
                    <label>{{ __('commissions.message') }}</label>
                    <textarea name="message" placeholder="{{ __('commissions.message_ph') }}">{{ old('message') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block">{{ __('commissions.send_request') }}</button>
                <p class="muted" style="font-size:12.5px;text-align:center;margin-top:10px">{{ __('commissions.free_note') }}</p>
            </form>
        </div>

        <div>
            <div class="panel" style="background:var(--surface-2)">
                <div style="font-weight:700;margin-bottom:12px">{{ __('commissions.how_works') }}</div>
                <div class="tl">
                    <div class="tl-item done"><div class="t">{{ __('commissions.step1') }}</div></div>
                    <div class="tl-item"><div class="t">{{ __('commissions.step2') }}</div></div>
                    <div class="tl-item"><div class="t">{{ __('commissions.step3') }}</div></div>
                    <div class="tl-item"><div class="t">{{ __('commissions.step4') }}</div></div>
                    <div class="tl-item"><div class="t">{{ __('commissions.step5') }}</div></div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
