<div wire:key="feed-{{ app()->getLocale() }}">
    @if ($pins === [])
        <div class="empty-state">@include('partials.ink-pattern')
            <div class="art">خَطّ</div>
            <h2 class="h1" style="font-size:var(--t-lg)">{{ __('shell.empty_feed_title') }}</h2>
            <p class="sub">{{ __('shell.empty_feed_sub') }}</p>
            <a class="btn btn-dark" href="{{ route('explore') }}">{{ __('shell.nav_explore') }}</a>
        </div>
    @else
        <div class="pin-grid" wire:loading.attr="aria-busy" wire:target="loadMore">
            @foreach ($pins as $pin)
                @include('partials.pin-card', ['pin' => $pin])
            @endforeach

            @for ($i = 0; $i < 3; $i++)
                <div class="skel" wire:loading.block wire:target="loadMore" aria-hidden="true"><span></span></div>
            @endfor
        </div>
    @endif

    @if ($hasMore)
        <div style="text-align:center;padding:10px 0 30px">
            <button type="button" class="btn btn-outline" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore">
                {{ __('shell.load_more') }}
            </button>
        </div>
    @endif
</div>
