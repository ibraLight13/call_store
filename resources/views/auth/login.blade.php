@extends('layouts.app')

@section('title', __('shell.auth_login_title'))

@section('content')
<div class="auth-wrap">
    <div class="auth-card panel">
        <div class="auth-mark">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21c3-1 5-3 8-8 2-3.5 4-8 8-10-1 5-3 9-6 12-2.5 2.5-5 4-10 6z"/><path d="M14 6l4 4"/></svg>
        </div>
        <h1 class="h1" style="text-align:center;font-size:24px">{{ __('shell.auth_login_title') }}</h1>
        <p class="sub" style="text-align:center">{{ __('shell.auth_login_sub') }}</p>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="field">
                <label for="email">{{ __('shell.email') }}</label>
                <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required autofocus>
                @error('email')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password">{{ __('shell.password') }}</label>
                <input id="password" name="password" type="password" class="input" required>
                @error('password')<div class="err">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary btn-block">{{ __('shell.enter') }}</button>
        </form>

        <p class="muted" style="text-align:center;font-size:13.5px;margin-top:16px">
            {{ __('shell.no_account') }}
            <a href="{{ route('register') }}" style="color:var(--primary-ink);font-weight:700">{{ __('shell.register') }}</a>
        </p>
    </div>
</div>
@endsection
