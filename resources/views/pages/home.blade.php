@extends('layouts.app')

@section('title', __('shell.home_title'))

@section('content')
<section class="page">
    <div class="eyebrow">{{ __('shell.home_eyebrow') }}</div>
    <h1 class="h1">{{ __('shell.home_title') }}</h1>
    <p class="sub">{{ __('shell.home_sub') }}</p>

    <div class="chips">
        @foreach ([
            '' => ['ar' => 'الكل', 'en' => 'All'],
            'quran' => ['ar' => 'قرآني', 'en' => 'Quranic'],
            'names' => ['ar' => 'أسماء', 'en' => 'Names'],
            'wedding' => ['ar' => 'زفاف', 'en' => 'Wedding'],
            'thuluth' => ['ar' => 'ثلث', 'en' => 'Thuluth'],
            'diwani' => ['ar' => 'ديواني', 'en' => 'Diwani'],
            'kufic' => ['ar' => 'كوفي', 'en' => 'Kufic'],
            'decor' => ['ar' => 'ديكور منزلي', 'en' => 'Home decor'],
            'quotes' => ['ar' => 'عبارات', 'en' => 'Quotes'],
        ] as $key => $label)
            <button class="chip {{ $loop->first ? 'on' : '' }}">{{ $label[app()->getLocale()] }}</button>
        @endforeach
    </div>

    <div class="pin-grid" style="columns:1">
        <div class="empty-state">
            <div class="art">خَطٌّ يُلامِسُ النّور</div>
            <div style="font-weight:700;font-size:16px;margin-bottom:4px">{{ __('shell.empty_feed_title') }}</div>
            <div>{{ __('shell.empty_feed_sub') }}</div>
        </div>
    </div>
</section>
@endsection
