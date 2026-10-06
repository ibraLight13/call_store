@extends('layouts.app')

@section('title', __('shell.home_title'))

@php
    // Wrap the translated accent phrase of a headline in a span (both pieces escaped first).
    $accent = fn (string $title, string $phrase): string => str_replace(e($phrase), '<span class="accent">'.e($phrase).'</span>', e($title));
@endphp

@section('content')
<section class="page">
    <div class="hero">
        @include('partials.ink-pattern')
        <div>
            <h1>{!! $accent(__('shell.home_title'), __('shell.home_title_accent')) !!}</h1>
            <p>{{ __('shell.home_sub') }}</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="{{ route('explore') }}">{{ __('shell.home_cta_browse') }}</a>
                <a class="btn btn-outline" href="{{ route('explore', ['type' => 'commission']) }}">{{ __('shell.home_cta_commission') }}</a>
            </div>
        </div>
        @if ($featured !== null)
            <div class="hero-feature">
                @include('partials.pin-card', ['pin' => array_merge($featured, ['aspect' => '4 / 3'])])
            </div>
        @endif
    </div>

    @include('partials.script-marquee', ['scripts' => $scripts])

    @livewire('feed')

    <section class="how reveal" aria-labelledby="how-title">
        <h2 id="how-title" class="sec-heading">{!! $accent(__('shell.how_title'), __('shell.how_title_accent')) !!}</h2>
        <p class="sub">{{ __('shell.how_sub') }}</p>
        <ol class="steps">
            @foreach ([1, 2, 3] as $n)
                <li class="step">
                    <span class="step-n" aria-hidden="true">{{ $n }}</span>
                    <h3>{{ __("shell.how_{$n}_t") }}</h3>
                    <p>{{ __("shell.how_{$n}_b") }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="faq reveal" aria-labelledby="faq-title">
        <h2 id="faq-title" class="sec-heading">{{ __('shell.faq_title') }}</h2>
        @foreach ([1, 2, 3, 4] as $n)
            <details class="faq-item">
                <summary>{{ __("shell.faq_{$n}_q") }}</summary>
                <p>{{ __("shell.faq_{$n}_a") }}</p>
            </details>
        @endforeach
    </section>

    <section class="cta-band reveal">
        @include('partials.ink-pattern')
        <h2>{{ __('shell.cta_title') }}</h2>
        <p>{{ __('shell.cta_sub') }}</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="{{ route('explore') }}">{{ __('shell.home_cta_browse') }}</a>
            <a class="btn btn-outline" href="{{ route('explore', ['type' => 'commission']) }}">{{ __('shell.home_cta_commission') }}</a>
        </div>
    </section>
</section>
@endsection
