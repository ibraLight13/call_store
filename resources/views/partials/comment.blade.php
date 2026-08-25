<div class="cmt" wire:key="comment-{{ $comment->id }}">
    <span class="dot" style="width:38px;height:38px">{{ mb_substr($comment->user->name, 0, 1) }}</span>
    <div class="body">
        <div class="nm">{{ $comment->user->name }}</div>
        <div class="tx">{{ $comment->body }}</div>
        <div class="mut">
            <span>{{ $comment->created_at?->diffForHumans() }}</span>
            @auth
                @if ($repliesByParent !== null && ! isset($isReply))
                    <button type="button" onclick="var f=document.getElementById('reply-{{ $comment->id }}');f.style.display=f.style.display==='none'?'block':'none'">{{ __('social.reply') }}</button>
                @endif
                @if ((int) auth()->user()->id === (int) $comment->user_id || (int) auth()->user()->hasRole('admin') || (int) $piece->seller_id === (int) auth()->user()->id)
                    <form method="POST" action="{{ route('comments.destroy', $comment) }}" style="display:inline" onsubmit="return confirm('{{ __('social.delete_confirm') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="err" style="background:none;border:none;padding:0;cursor:pointer">{{ __('social.delete') }}</button>
                    </form>
                @endif
            @endauth
        </div>

        @if (! isset($isReply))
            <div id="reply-{{ $comment->id }}" style="display:none;margin-top:8px">
                <form method="POST" action="{{ route('comments.store', $piece) }}">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                    <textarea name="body" required minlength="2" maxlength="2000" placeholder="{{ __('social.reply_ph') }}"></textarea>
                    <div style="text-align:end;margin-top:6px">
                        <button type="submit" class="btn btn-dark btn-sm">{{ __('social.reply') }}</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
@if (! isset($isReply))
    @foreach (($repliesByParent[$comment->id] ?? collect()) as $reply)
        @include('partials.comment', ['comment' => $reply, 'isReply' => true])
    @endforeach
@endif
