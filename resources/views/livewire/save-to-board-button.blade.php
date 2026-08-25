<div class="save-wrap" wire:key="save-{{ $piece->id }}">
    <button type="button" class="btn btn-sm {{ count($savedBoardIds) > 0 ? 'btn-gold' : 'btn-ghost' }}" wire:click="toggleOpen">
        🔖 {{ count($savedBoardIds) > 0 ? __('social.saved') : __('social.save') }}
    </button>

    @if ($open)
        <div class="panel save-pop">
            <div style="font-weight:700;margin-bottom:10px">{{ __('social.save_to_board') }}</div>
            @forelse ($boards as $board)
                @if (in_array((int) $board['id'], $savedBoardIds, true))
                    <div class="pill on" style="display:block;text-align:center;margin-bottom:8px;cursor:default">✓ {{ $board['name'] }}</div>
                @else
                    <button type="button" class="pill" style="display:block;width:100%;margin-bottom:8px" wire:click="saveTo({{ $board['id'] }})">
                        + {{ $board['name'] }}
                    </button>
                @endif
            @empty
                <p class="muted" style="font-size:13px;margin:0 0 10px">{{ __('social.no_boards') }}</p>
            @endforelse

            <div style="display:flex;gap:8px">
                <input type="text" class="input" style="padding:8px 12px" placeholder="{{ __('social.new_board_ph') }}" wire:model.live.debounce.300ms="newBoardName" maxlength="100">
                <button type="button" class="btn btn-dark btn-sm" wire:click="createAndSave" wire:loading.attr="disabled">＋</button>
            </div>
            @error('newBoardName')<div class="err">{{ $message }}</div>@enderror
        </div>
    @endif
</div>
