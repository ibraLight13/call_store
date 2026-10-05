<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('shell.nav_home')) — خَطّ</title>
    <meta name="description" content="@yield('meta_description', __('shell.tagline'))">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:site_name" content="خَطّ">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', __('shell.nav_home')) — خَطّ">
    <meta property="og:description" content="@yield('meta_description', __('shell.tagline'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary">

    {{-- Before first paint: a saved choice pins the theme; otherwise color-scheme follows the OS. --}}
    <script>(function(){var m=document.cookie.match(/(?:^|;\s*)theme=(light|dark)/);if(m)document.documentElement.dataset.theme=m[1];})();</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Aref+Ruqaa:wght@400;700&family=Reem+Kufi:wght@400;500;700&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<a class="skip-link" href="#main-content">{{ __('shell.skip_to_content') }}</a>

<header class="topbar">
    <a class="brand" href="{{ route('home') }}">خَطّ</a>

    <nav class="topnav" aria-label="{{ __('shell.nav_main') }}">
        <a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>{{ __('shell.nav_home') }}</a>
        <a href="{{ route('explore') }}" @if (request()->routeIs('explore')) aria-current="page" @endif>{{ __('shell.nav_explore') }}</a>
    </nav>

    <form class="search" role="search" action="{{ route('explore') }}" method="get">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-3.5-3.5"/></svg>
        <input name="q" value="{{ request('q') }}" placeholder="{{ __('shell.search_ph') }}" aria-label="{{ __('shell.search') }}">
    </form>

    <div class="controls">
        <a class="iconbtn search-mobile" href="{{ route('explore') }}" aria-label="{{ __('shell.search') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-3.5-3.5"/></svg>
        </a>

        @auth
            @php($unreadCount = auth()->user()->unreadNotifications()->count())
            <a class="iconbtn hide-mobile" href="{{ route('notifications.index') }}" aria-label="{{ __('social.notifications') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                @if ($unreadCount > 0)<span class="count">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
            </a>
            <a class="iconbtn hide-mobile" href="{{ route('cart.index') }}" aria-label="{{ __('shell.nav_cart') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.4 12.4a1.6 1.6 0 0 0 1.6 1.3h8.6a1.6 1.6 0 0 0 1.6-1.3L22 7H6"/></svg>
            </a>
        @endauth

        <details class="menu">
            <summary aria-label="{{ __('shell.account') }}">
                @auth
                    <span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                @else
                    <span class="iconbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></span>
                @endauth
            </summary>
            <div class="menu-pop">
                @auth
                    <a href="{{ auth()->user()->profileUrl() }}">{{ __('shell.nav_profile') }}</a>
                    <a href="{{ route('boards.index') }}">{{ __('social.my_boards') }}</a>
                    <a href="{{ route('orders.index') }}">{{ __('shell.nav_orders') }}</a>
                    <a href="{{ route('commissions.index') }}">{{ __('commissions.my_commissions') }}</a>
                    <a href="{{ route('notifications.index') }}">{{ __('social.notifications') }}</a>
                    <a href="{{ route('settings.profile.edit') }}">{{ __('shell.settings') }}</a>
                    @role('seller')
                        <div class="menu-sep"></div>
                        <a href="{{ route('create') }}">{{ __('shell.nav_create') }}</a>
                        <a href="{{ route('seller.orders.index') }}">{{ __('shell.nav_dash') }}</a>
                    @endrole
                    @role('admin')
                        <a href="/admin">{{ __('shell.nav_admin') }}</a>
                    @endrole
                    <div class="menu-sep"></div>
                @endauth
                <div class="menu-lang" aria-label="{{ __('shell.language') }}">
                    <a href="{{ request()->fullUrlWithQuery(['lang' => 'ar']) }}" @if (app()->getLocale() === 'ar') aria-current="true" @endif>العربية</a>
                    <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" @if (app()->getLocale() === 'en') aria-current="true" @endif>English</a>
                </div>
                <button type="button" onclick="toggleTheme()">{{ __('shell.theme') }}</button>
                @auth
                    <button type="submit" form="logout-form">{{ __('shell.logout') }}</button>
                @endauth
            </div>
        </details>

        @guest
            <a class="btn btn-dark btn-sm" href="{{ route('login') }}">{{ __('shell.login') }}</a>
        @endguest
    </div>
</header>

<main id="main-content">
    @yield('content')
</main>

<nav class="tabbar" aria-label="{{ __('shell.nav_main') }}">
    <a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5V21H3z"/></svg>{{ __('shell.nav_home') }}
    </a>
    <a href="{{ route('explore') }}" @if (request()->routeIs('explore')) aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-3.5-3.5"/></svg>{{ __('shell.nav_explore') }}
    </a>
    @auth
        <a href="{{ route('boards.index') }}" @if (request()->routeIs('boards.*')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="3" y="3" width="7" height="10" rx="1.5"/><rect x="14" y="3" width="7" height="6" rx="1.5"/><rect x="14" y="13" width="7" height="8" rx="1.5"/><rect x="3" y="17" width="7" height="4" rx="1.5"/></svg>{{ __('social.my_boards') }}
        </a>
        <a href="{{ route('cart.index') }}" @if (request()->routeIs('cart.*') || request()->routeIs('checkout.*')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.4 12.4a1.6 1.6 0 0 0 1.6 1.3h8.6a1.6 1.6 0 0 0 1.6-1.3L22 7H6"/></svg>{{ __('shell.nav_cart') }}
        </a>
        <a href="{{ auth()->user()->profileUrl() }}" @if (request()->routeIs('profile.show')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>{{ __('shell.nav_profile') }}
        </a>
    @endauth
</nav>

<form method="POST" action="{{ route('logout') }}" id="logout-form" hidden>@csrf</form>

<script>
function toggleTheme(){
    var root = document.documentElement;
    var current = root.dataset.theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    var next = current === 'dark' ? 'light' : 'dark';
    root.dataset.theme = next;
    document.cookie = 'theme=' + next + ';path=/;max-age=31536000;samesite=lax';
}
</script>
</body>
</html>
