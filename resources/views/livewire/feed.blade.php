<div class="pin-grid" wire:key="feed-{{ app()->getLocale() }}">
    @foreach ($pins as $pin)
        <article class="pcard" wire:key="pin-{{ $pin['id'] }}">
            <a href="{{ $pin['url'] }}" class="pcard-media" aria-label="{{ $pin['text'] }}">
                @if ($pin['img'])
                    <img src="{{ $pin['img'] }}" alt="{{ $pin['text'] }}" loading="lazy"
                         width="480" height="360" style="object-fit:cover;width:100%;height:100%;display:block">
                @else
                    <span class="pcard-art" style="background:{{ $pin['bg'] }};color:{{ $pin['ink'] }};font-family:{{ $pin['font'] }};font-weight:{{ $pin['weight'] }};font-size:{{ $pin['size'] }}px">{{ $pin['text'] }}</span>
                    <span class="paper-edge"></span>
                @endif
                @if ($pin['scriptName'])
                    <span class="scr-chip">{{ $pin['scriptName'] }}</span>
                @endif
                @if ($pin['sold'])
                    <span class="badge badge-gold pcard-sold">{{ __('commerce.sold') }}</span>
                @endif
            </a>

            <div class="pcard-body">
                <a href="{{ $pin['url'] }}" class="pcard-title">{{ $pin['text'] }}</a>

                <div class="pcard-meta">
                    <a href="{{ $pin['sellerUrl'] }}" class="pcard-seller" title="{{ $pin['sellerName'] }}">
                        <span class="dot" style="width:22px;height:22px;font-size:10px">{{ $pin['sellerInitial'] }}</span>
                        <span class="nm">{{ $pin['sellerName'] }}</span>
                    </a>

                    @if ($pin['isReady'])
                        <span class="pcard-price">{{ $pin['price'] }} <small>SAR</small></span>
                    @else
                        <span class="badge badge-primary" style="font-size:10.5px">{{ __('shell.t_commission') }}</span>
                    @endif
                </div>
            </div>
        </article>
    @endforeach

    @if ($hasMore)
        <div style="grid-column:1/-1;text-align:center;padding:10px 0 30px">
            <button type="button" class="btn btn-outline" wire:click="loadMore" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="loadMore">{{ __('shell.load_more') }}</span>
                <span wire:loading wire:target="loadMore">…</span>
            </button>
        </div>
    @endif
</div>
