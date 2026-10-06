@extends('layouts.app')

@section('title', $home['hero']['title'])

@php
    // Wrap the accent phrase of a headline in a span (both pieces escaped first).
    $accent = fn (string $title, string $phrase): string => $phrase === ''
        ? e($title)
        : str_replace(e($phrase), '<span class="accent">'.e($phrase).'</span>', e($title));
    $hero = $home['hero'];
@endphp

@section('content')
<section class="page">
    <div class="hero">
        @include('partials.ink-pattern')
        <div>
            <h1>{!! $accent($hero['title'], $hero['title_accent']) !!}</h1>
            <p>{{ $hero['subtitle'] }}</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="{{ route('explore') }}">{{ $hero['cta_browse'] }}</a>
                <a class="btn btn-outline" href="{{ route('explore', ['type' => 'commission']) }}">{{ $hero['cta_commission'] }}</a>
            </div>
        </div>
        @if ($featured !== null)
            <div class="hero-feature">
                @include('partials.pin-card', ['pin' => array_merge($featured, ['aspect' => '4 / 3'])])
            </div>
        @endif
    </div>

    @if ($home['marquee']['visible'])
        @include('partials.script-marquee', ['scripts' => $scripts])
    @endif

    @if ($home['feed']['visible'])
        @livewire('feed')
    @endif

    @if ($home['how']['visible'])
        <section class="how reveal" aria-labelledby="how-title">
            <h2 id="how-title" class="sec-heading">{!! $accent($home['how']['title'], $home['how']['title_accent']) !!}</h2>
            <p class="sub">{{ $home['how']['subtitle'] }}</p>
            @if ($home['how']['steps'] !== [])
                <ol class="steps">
                    @foreach ($home['how']['steps'] as $step)
                        <li class="step">
                            <span class="step-n" aria-hidden="true">{{ $loop->iteration }}</span>
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['body'] }}</p>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    @endif

    @if ($home['faq']['visible'] && $home['faq']['items'] !== [])
        <section class="faq reveal" aria-labelledby="faq-title">
            <h2 id="faq-title" class="sec-heading">{{ $home['faq']['title'] }}</h2>
            @foreach ($home['faq']['items'] as $item)
                <details class="faq-item">
                    <summary>{{ $item['question'] }}</summary>
                    <p>{{ $item['answer'] }}</p>
                </details>
            @endforeach
        </section>
    @endif

    @if ($home['cta']['visible'])
        <section class="cta-band reveal">
            @include('partials.ink-pattern')
            <h2>{{ $home['cta']['title'] }}</h2>
            <p>{{ $home['cta']['subtitle'] }}</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="{{ route('explore') }}">{{ $hero['cta_browse'] }}</a>
                <a class="btn btn-outline" href="{{ route('explore', ['type' => 'commission']) }}">{{ $hero['cta_commission'] }}</a>
            </div>
        </section>
    @endif
</section>
@endsection
