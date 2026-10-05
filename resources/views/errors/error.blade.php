@extends('layouts.app')

@section('title', __("errors.title_".$exception->getStatusCode()))

@section('content')
<div class="auth-wrap ink-host">
    @include('partials.ink-pattern')
    <div class="auth-card" style="text-align:center">
        <div class="mark" style="width:64px;height:64px;border-radius:18px;margin:0 auto 16px;display:grid;place-items:center;background:linear-gradient(150deg,var(--primary),var(--primary-ink));color:#fff;font-family:var(--f-disp);font-size:30px">خ</div>
        <h1 class="h1" style="font-size:52px;margin-bottom:8px">{{ $exception->getStatusCode() }}</h1>
        <p class="sub" style="margin-bottom:20px">{{ __("errors.message_".$exception->getStatusCode()) }}</p>
        <a href="{{ route('home') }}" class="btn btn-dark">{{ __('errors.back_home') }}</a>
    </div>
</div>
@endsection
