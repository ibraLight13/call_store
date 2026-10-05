@extends('layouts.app')

@section('title', __('shell.home_title'))

@section('content')
<section class="page">
    <div class="hero">
        @include('partials.ink-pattern')
        <div>
            <h1>{{ __('shell.home_title') }}</h1>
            <p>{{ __('shell.home_sub') }}</p>
        </div>
        @if ($featured !== null)
            <div class="hero-feature">
                @include('partials.pin-card', ['pin' => array_merge($featured, ['aspect' => '4 / 3'])])
            </div>
        @endif
    </div>

    @include('partials.script-ribbon', ['scripts' => $scripts, 'activeId' => null])

    @livewire('feed')
</section>
@endsection
