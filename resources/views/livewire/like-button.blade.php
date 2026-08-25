<div class="like-wrap">
    <button
        type="button"
        wire:click="toggle"
        @class(['btn', 'btn-sm', 'btn-ghost' => $liked, 'btn-outline' => ! $liked])
    >
        {{ $liked ? '♥' : '♡' }}
        <span>{{ $count }}</span>
    </button>
</div>
