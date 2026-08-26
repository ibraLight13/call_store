!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
      data-theme="{{ request()->cookie('theme', 'light') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Aref+Ruqaa:wght@400;700&family=Reem+Kufi:wght@400;500;700&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<a class="skip-link" href="#main-content">{{ __('shell.skip_to_content') }}</a>

<header class="topbar">
    <a class="brand" href="{{ route('home') }}">
        <div class="mark">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21c3-1 5-3 8-8 2-3.5 4-8 8-10-1 5-3 9-6 12-2.5 2.5-5 4-10 6z"/><path d="M14 6l4 4"/></svg>
        </div>
        <div>
            <div class="name">خَطّ</div>
            <div class="tag">{{ __('shell.tagline') }}</div>
        </div>
    </a>

    <form class="search" action="{{ route('explore') }}" method="get">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-3.5-3.5"/></svg>
        <input name="q" placeholder="{{ __('shell.search_ph') }}">
    </form>

    <div class="controls">
        <div class="seg" title="{{ app()->getLocale() === 'ar' ? 'Language' : 'اللغة' }}">
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'ar']) }}" class="{{ app()->getLocale() === 'ar' ? 'on' : '' }}">ع</a>
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="{{ app()->getLocale() === 'en' ? 'on' : '' }}">EN</a>
        </div>
        @auth
            @php($unreadCount = auth()->user()->unreadNotifications()->count())
            <a class="iconbtn" href="{{ route('notifications.index') }}" title="{{ __('social.notifications') }}" style="position:relative">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                @if ($unreadCount > 0)
                    <span style="position:absolute;top:-5px;inset-inline-end:-5px;background:var(--danger);color:#fff;font-size:10px;font-weight:700;min-width:17px;height:17px;border-radius:9px;display:grid;place-items:center;padding:0 4px">{{ $unreadCount > 9 ? '٩+' : $unreadCount }}</span>
                @endif
            </a>
        @endauth
        <button class="iconbtn" id="thBtn" title="{{ __('shell.theme') }}" onclick="toggleTheme()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/></svg>
        </button>
        @auth
            <a class="avatar" href="{{ auth()->user()->profileUrl() }}" title="{{ __('shell.account') }}">{{ mb_substr(auth()->user()->name, 0, 1) }}</a>
        @endauth
        @guest
            <a class="btn btn-dark btn-sm" href="{{ route('login') }}">{{ __('shell.login') }}</a>
        @endguest
    </div>
</header>

<div class="shell">
    <nav class="rail">
        <a class="rail-item {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10.5 12 3l9 7.5V21H3z"/></svg><span>{{ __('shell.nav_home') }}</span>
        </a>
        <a class="rail-item {{ request()->routeIs('explore') ? 'active' : '' }}" href="{{ route('explore') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7"/><path d="m21 21-3.5-3.5"/></svg><span>{{ __('shell.nav_explore') }}</span>
        </a>
        @role('seller')
        <a class="rail-item {{ request()->routeIs('create') ? 'active' : '' }}" href="{{ route('create') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14"/></svg><span>{{ __('shell.nav_create') }}</span>
        </a>
        @endrole
        @role('seller')
        <a class="rail-item {{ request()->routeIs('seller.orders.*') ? 'active' : '' }}" href="{{ route('seller.orders.index') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 3h8v8H3zM13 3h8v5h-8zM13 12h8v9h-8zM3 15h8v6H3z"/></svg><span>{{ __('shell.nav_dash') }}</span>
        </a>
        @endrole
        @role('admin')
        <a class="rail-item" href="/admin">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3l7 3v6c0 5-3 7-7 9-4-2-7-4-7-9V6z"/></svg><span>{{ __('shell.nav_admin') }}</span>
        </a>
        @endrole
        @auth
        <a class="rail-item {{ request()->routeIs('boards.*') ? 'active' : '' }}" href="{{ route('boards.index') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="10" rx="1.5"/><rect x="14" y="3" width="7" height="6" rx="1.5"/><rect x="14" y="13" width="7" height="8" rx="1.5"/><rect x="3" y="17" width="7" height="4" rx="1.5"/></svg><span>{{ __('social.my_boards') }}</span>
        </a>
        <a class="rail-item {{ request()->routeIs('cart.index') || request()->routeIs('checkout.*') ? 'active' : '' }}" href="{{ route('cart.index') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.4 12.4a1.6 1.6 0 0 0 1.6 1.3h8.6a1.6 1.6 0 0 0 1.6-1.3L22 7H6"/></svg><span>{{ __('shell.nav_cart') }}</span>
        </a>
        <a class="rail-item {{ request()->routeIs('orders.index') || request()->routeIs('orders.show') ? 'active' : '' }}" href="{{ route('orders.index') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6 9 17l-5-5"/><path d="M15 3h6v6" opacity=".5"/></svg><span>{{ __('shell.nav_orders') }}</span>
        </a>
        <a class="rail-item {{ request()->routeIs('profile.show') || request()->routeIs('settings.profile.*') ? 'active' : '' }}" href="{{ auth()->user()->profileUrl() }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg><span>{{ __('shell.nav_profile') }}</span>
        </a>
        @endauth
        <div class="spacer"></div>
    </nav>

    <main id="main-content">
        @yield('content')
    </main>
</div>

<form method="POST" action="{{ route('logout') }}" class="hide" id="logout-form">@csrf</form>

<script>
function toggleTheme(){
    const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = next;
    document.cookie = 'theme=' + next + ';path=/;max-age=31536000;samesite=lax';
}
(function(){
    const m = document.cookie.match(/(?:^|;\s*)theme=(\w+)/);
    if (m && m[1] !== document.documentElement.dataset.theme) {
        document.documentElement.dataset.theme = m[1];
    }
})();
</script>
</body>
</html>
