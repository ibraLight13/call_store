@extends('layouts.app')

@section('title', $title)

@section('content')
<section class="page" style="max-width:720px">
    <h1 class="h1">{{ $title }}</h1>
    <p class="sub">{{ __('shell.soon_sub') }}</p>
    <div class="empty-state" style="grid-column:auto">
        <div class="art">قريبًا</div>
    </div>
    @if ($withLogout ?? false)
        <form method="POST" action="{{ route('logout') }}" style="margin-top:20px">
            @csrf
            <button type="submit" class="btn btn-ghost">{{ __('shell.logout') }}</button>
        </form>
    @endif
</section>
@endsection
