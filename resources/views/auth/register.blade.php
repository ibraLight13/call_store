@extends('layouts.app')

@section('title', __('shell.auth_register_title'))

@section('content')
<div class="auth-wrap">
    <div class="auth-card panel">
        <div class="auth-mark">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21c3-1 5-3 8-8 2-3.5 4-8 8-10-1 5-3 9-6 12-2.5 2.5-5 4-10 6z"/><path d="M14 6l4 4"/></svg>
        </div>
        <h1 class="h1" style="text-align:center;font-size:24px">{{ __('shell.auth_register_title') }}</h1>
        <p class="sub" style="text-align:center">{{ __('shell.auth_register_sub') }}</p>

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="field">
                <label for="name">{{ __('shell.name') }}</label>
                <input id="name" name="name" type="text" class="input" value="{{ old('name') }}" required autofocus>
                @error('name')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="username">{{ __('shell.username') }} <span class="muted" dir="ltr">{{ url('/@') }}</span></label>
                <input id="username" name="username" type="text" class="input" dir="ltr" value="{{ old('username') }}" required>
                @error('username')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="email">{{ __('shell.email') }}</label>
                <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required>
                @error('email')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="field">
                    <label for="password">{{ __('shell.password') }}</label>
                    <input id="password" name="password" type="password" class="input" required>
                    @error('password')<div class="err">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">{{ __('shell.password_confirmation') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="input" required>
                </div>
            </div>
            <div class="field">
                <label>{{ __('shell.account_type') }}</label>
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:14px;padding:10px;border:1.5px solid var(--line);border-radius:12px;margin-bottom:8px;cursor:pointer">
                    <input type="radio" name="account_type" value="buyer" checked> {{ __('shell.buyer') }}
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:14px;padding:10px;border:1.5px solid var(--line);border-radius:12px;cursor:pointer">
                    <input type="radio" name="account_type" value="seller"> {{ __('shell.seller') }}
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-block">{{ __('shell.create_account') }}</button>
        </form>

        <p class="muted" style="text-align:center;font-size:13.5px;margin-top:16px">
            {{ __('shell.have_account') }}
            <a href="{{ route('login') }}" style="color:var(--primary-ink);font-weight:700">{{ __('shell.login') }}</a>
        </p>
    </div>
</div>
@endsection
