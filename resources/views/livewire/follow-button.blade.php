<div class="follow-wrap">
    @guest
        <a href="{{ route('login') }}" class="btn btn-dark btn-sm">{{ __('shell.follow') }}</a>
    @else
        <button
            type="button"
            wire:click="toggle"
            @class(['btn', 'btn-sm', 'btn-ghost' => $following, 'btn-dark' => ! $following])
        >
            @if ($following)
                {{ __('shell.unfollow') }}
            @else
                {{ __('shell.follow') }}
            @endif
        </button>
    @endguest
</div>
