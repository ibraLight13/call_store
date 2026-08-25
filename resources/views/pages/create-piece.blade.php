@extends('layouts.app')

@section('title', __('catalog.create_title'))

@section('content')
<section class="page" style="max-width:900px">
    <h1 class="h1">{{ __('catalog.create_title') }}</h1>
    <p class="sub">{{ __('catalog.create_sub') }}</p>

    @if (session('status'))
        <div class="panel" style="border-inline-start:4px solid var(--primary);margin-bottom:18px;padding:13px 18px;font-weight:600;color:var(--primary-ink)">
            ✓ {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('create.store') }}" enctype="multipart/form-data" class="panel" style="margin-bottom:16px">
        @csrf
        <div class="grid2">
            <div>
                <div class="field">
                    <label for="text">{{ __('catalog.text') }} <span class="muted" style="font-weight:400">{{ __('catalog.text_hint') }}</span></label>
                    <input id="text" name="text" class="input arabic-input" value="{{ old('text') }}" required maxlength="500">
                    @error('text')<div class="err">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="field">
                        <label for="script_id">{{ __('catalog.script') }}</label>
                        <select id="script_id" name="script_id" class="input">
                            <option value="">—</option>
                            @foreach ($scripts as $script)
                                <option value="{{ $script->id }}" {{ old('script_id') == $script->id ? 'selected' : '' }}>
                                    {{ $script->{'name_'.app()->getLocale()} }} — {{ $script->name_en }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="text_language">{{ __('catalog.text_language') }}</label>
                        <select id="text_language" name="text_language" class="input">
                            <option value="ar" {{ old('text_language', 'ar') === 'ar' ? 'selected' : '' }}>العربية</option>
                            <option value="en" {{ old('text_language') === 'en' ? 'selected' : '' }}>English</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label for="transliteration">{{ __('catalog.transliteration') }} <span class="muted" style="font-weight:400">{{ __('catalog.transliteration_hint') }}</span></label>
                    <input id="transliteration" name="transliteration" dir="ltr" class="input" value="{{ old('transliteration') }}">
                </div>

                <div class="row">
                    <div class="field">
                        <label for="medium_id">{{ __('catalog.medium') }}</label>
                        <select id="medium_id" name="medium_id" class="input">
                            <option value="">—</option>
                            @foreach ($mediums as $medium)
                                <option value="{{ $medium->id }}" {{ old('medium_id') == $medium->id ? 'selected' : '' }}>{{ $medium->{'name_'.app()->getLocale()} }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="theme_id">{{ __('catalog.theme') }}</label>
                        <select id="theme_id" name="theme_id" class="input">
                            <option value="">—</option>
                            @foreach ($themes as $theme)
                                <option value="{{ $theme->id }}" {{ old('theme_id') == $theme->id ? 'selected' : '' }}>{{ $theme->{'name_'.app()->getLocale()} }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label>{{ __('catalog.color') }}</label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        @foreach ($colors as $color)
                            <label style="cursor:pointer;position:relative">
                                <input type="radio" name="color_id" value="{{ $color->id }}" {{ old('color_id') == $color->id ? 'checked' : '' }} style="position:absolute;opacity:0">
                                <span class="swatch" style="background:{{ $color->hex }}" title="{{ $color->{'name_'.app()->getLocale()} }}"></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="row">
                    <div class="field"><label for="width_cm">{{ __('catalog.width') }}</label><input id="width_cm" name="width_cm" type="number" min="5" max="500" class="input" value="{{ old('width_cm') }}"></div>
                    <div class="field"><label for="height_cm">{{ __('catalog.height') }}</label><input id="height_cm" name="height_cm" type="number" min="5" max="500" class="input" value="{{ old('height_cm') }}"></div>
                </div>
            </div>

            <div>
                <div class="field">
                    <label>{{ __('catalog.photos') }}</label>
                    <div class="drop">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 16V4M6 10l6-6 6 6"/><path d="M4 20h16" opacity=".5"/></svg>
                        <div style="font-weight:700">{{ __('catalog.photos') }}</div>
                        <div style="font-size:12px">{{ __('catalog.photos_hint') }}</div>
                        <input type="file" name="photos[]" multiple accept="image/*" style="margin-top:10px;width:100%">
                    </div>
                    @error('photos.*')<div class="err">{{ $message }}</div>@enderror
                    <p class="muted" style="font-size:12.5px;margin-top:10px">🛡️ {{ __('catalog.photos_note') }}</p>
                </div>

                <div class="field">
                    <label>{{ __('catalog.listing_type') }}</label>
                    <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:14px;padding:10px;border:1.5px solid var(--line);border-radius:12px;margin-bottom:8px;cursor:pointer">
                        <input type="radio" name="listing_type" value="ready" checked> {{ __('catalog.ready') }}
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:14px;padding:10px;border:1.5px solid var(--line);border-radius:12px;cursor:pointer">
                        <input type="radio" name="listing_type" value="commission"> {{ __('catalog.commission') }}
                    </label>
                </div>

                <div class="row">
                    <div class="field">
                        <label for="price">{{ __('catalog.price', ['currency' => config('catalog.currency')]) }}</label>
                        <input id="price" name="price" type="number" step="0.01" min="0" class="input" value="{{ old('price') }}">
                        <div class="muted" style="font-size:11.5px;margin-top:4px">{{ __('catalog.price_hint') }}</div>
                        @error('price')<div class="err">{{ $message }}</div>@enderror
                    </div>
                    <div></div>
                </div>

                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13.5px;margin-bottom:14px">
                    <input type="checkbox" name="unique_stock" value="1" checked> {{ __('catalog.unique_stock_label') }}
                </label>

                <div class="field">
                    <label for="description">{{ __('catalog.description') }}</label>
                    <textarea id="description" name="description" placeholder="{{ __('catalog.description_ph') }}">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:10px">
            <button type="submit" name="submit_for_review" value="1" class="btn btn-primary">{{ __('catalog.submit_review') }}</button>
            <button type="submit" name="submit_for_review" value="0" class="btn btn-ghost">{{ __('catalog.save_draft') }}</button>
        </div>
    </form>
</section>
@endsection
