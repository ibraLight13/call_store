@extends('layouts.app')

@section('title', __('shell.edit_profile'))

@section('content')
<section class="page" style="max-width:760px">
    <div class="eyebrow">{{ __('shell.settings') }}</div>
    <h1 class="h1">{{ __('shell.edit_profile') }}</h1>
    <p class="sub">{{ __('shell.edit_profile_sub') }}</p>

    @if (session('status'))
        <div class="panel" style="border-inline-start:4px solid var(--primary);margin-bottom:18px;padding:13px 18px;font-weight:600;color:var(--primary-ink)">
            ✓ {{ session('status') }}
        </div>
    @endif

    <div style="display:flex;justify-content:flex-end;margin-bottom:10px">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm">{{ __('shell.logout') }}</button>
        </form>
    </div>

    <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data" class="panel">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="field">
                <label for="name">{{ __('shell.name') }}</label>
                <input id="name" name="name" type="text" class="input" value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="username">{{ __('shell.username') }}</label>
                <input id="username" name="username" type="text" class="input" dir="ltr" value="{{ old('username', $user->username) }}" required>
                <div style="font-size:12px;color:var(--ink-faint);margin-top:5px" dir="{{ app()->getLocale() === 'ar' ? 'ltr' : 'rtl' }}">{{ url('/@') }}…</div>
                @error('username')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="field">
            <label for="bio">{{ __('shell.bio') }}</label>
            <textarea id="bio" name="bio" placeholder="{{ __('shell.bio_ph') }}">{{ old('bio', $user->bio) }}</textarea>
            @error('bio')<div class="err">{{ $message }}</div>@enderror
        </div>

        <div class="row">
            <div class="field">
                <label for="locale">{{ __('shell.pref_lang') }}</label>
                <select id="locale" name="locale" class="input">
                    <option value="" {{ old('locale', $user->locale) === null ? 'selected' : '' }}>{{ __('shell.lang_auto') }}</option>
                    <option value="ar" {{ old('locale', $user->locale) === 'ar' ? 'selected' : '' }}>العربية</option>
                    <option value="en" {{ old('locale', $user->locale) === 'en' ? 'selected' : '' }}>English</option>
                </select>
            </div>
            <div></div>
        </div>

        <div class="divider"></div>

        <div class="row">
            <div class="field">
                <label for="avatar">{{ __('shell.avatar') }}</label>
                @if ($user->avatar_path)
                    <img src="{{ Storage::disk('public')->url($user->avatar_path) }}" alt="" style="width:64px;height:64px;border-radius:50%;object-fit:cover;margin-bottom:8px">
                @endif
                <input id="avatar" name="avatar" type="file" accept="image/*" class="input">
                @error('avatar')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="cover">{{ __('shell.cover') }}</label>
                @if ($user->cover_path)
                    <img src="{{ Storage::disk('public')->url($user->cover_path) }}" alt="" style="width:100%;height:52px;object-fit:cover;border-radius:10px;margin-bottom:8px">
                @endif
                <input id="cover" name="cover" type="file" accept="image/*" class="input">
                @error('cover')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div style="display:flex;gap:10px;align-items:center">
            <button type="submit" class="btn btn-dark">{{ __('shell.save_changes') }}</button>
            <a href="{{ $user->profileUrl() }}" class="btn btn-ghost btn-sm">{{ __('shell.view_public_profile') }}</a>
        </div>
    </form>
</section>
@endsection
