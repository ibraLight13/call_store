{{-- Gallery-wall card: framed artwork + museum wall label. Takes $pin from App\Support\PinPresenter. --}}
<article class="work" wire:key="pin-{{ $pin['id'] }}">
    <a href="{{ $pin['url'] }}" class="frame" aria-label="{{ $pin['text'] }}">
        <span class="frame-art" style="aspect-ratio:{{ $pin['aspect'] }}">
            @if ($pin['img'])
                <img src="{{ $pin['img'] }}" alt="{{ $pin['text'] }}" loading="lazy" width="480" height="360"
                     onerror="this.hidden=true;this.nextElementSibling.hidden=false">
            @endif
            <span class="typeset" @if ($pin['img']) hidden @endif
                  style="background:{{ $pin['bg'] }};color:{{ $pin['ink'] }};font-family:{{ $pin['font'] }};font-weight:{{ $pin['weight'] }};font-size:min({{ $pin['size'] }}px, 14cqi)">{{ $pin['text'] }}</span>
        </span>
    </a>

    <div class="wall-label">
        @if ($pin['img'])
            <a href="{{ $pin['url'] }}" class="wl-title">{{ $pin['text'] }}</a>
        @endif
        <a href="{{ $pin['sellerUrl'] }}" class="wl-meta">{{ $pin['sellerName'] }}@if ($pin['scriptName'] !== ''){{ __('shell.list_sep') }}{{ $pin['scriptName'] }}@endif</a>
        @if ($pin['sold'])
            <span class="wl-price is-sold">{{ __('commerce.sold') }}</span>
        @elseif ($pin['isReady'])
            <span class="wl-price">{{ $pin['price'] }} {{ config('catalog.currency') }}</span>
        @else
            <span class="wl-price is-commission">{{ __('shell.t_commission') }}</span>
        @endif
    </div>
</article>
