<div class="pin-grid" wire:key="feed-{{ app()->getLocale() }}">
    @foreach ($pins as $i => $pin)
        <div class="pin" wire:key="pin-{{ $pin['id'] }}">
            <a href="{{ $pin['url'] }}" class="canvas" style="background:{{ $pin['bg'] }};display:grid">
                <span class="scr-chip">{{ $pin['scriptName'] }}</span>
                <div class="art" style="color:{{ $pin['ink'] }};font-family:{{ $pin['font'] }};font-weight:{{ $pin['weight'] }};font-size:{{ $pin['size'] }}px">{{ $pin['text'] }}</div>
                <div class="paper-edge"></div>
            </a>
            <div class="meta">
                <div class="who">
                    <a href="{{ $pin['sellerUrl'] }}" class="dot" title="{{ $pin['sellerName'] }}">{{ $pin['sellerInitial'] }}</a>
                    <a href="{{ $pin['sellerUrl'] }}" class="nm">{{ $pin['sellerName'] }}</a>
                </div>
                @if ($pin['isReady'])
                    <span class="pr">{{ $pin['price'] }} <span style="font-size:11px;color:var(--ink-faint)">SAR</span></span>
                @else
                    <span class="badge badge-primary" style="font-size:11px">{{ __('shell.t_commission') }}</span>
                @endif
            </div>
        </div>
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
