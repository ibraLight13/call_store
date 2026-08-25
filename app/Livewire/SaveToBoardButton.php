<?php

namespace App\Livewire;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Social\Actions\CreateBoard;
use App\Domain\Social\Actions\SaveToBoard;
use App\Domain\Social\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

final class SaveToBoardButton extends Component
{
    public Piece $piece;

    public bool $open = false;

    /** @var list<array{id: int, name: string}> */
    public array $boards = [];

    /** @var array<int, int> */
    public array $savedBoardIds = [];

    public string $newBoardName = '';

    public function mount(Piece $piece): void
    {
        $this->refreshBoards();
    }

    public function refreshBoards(): void
    {
        $viewer = Auth::user();

        if (! $viewer instanceof User) {
            $this->boards = [];
            $this->savedBoardIds = [];

            return;
        }

        $list = [];

        $rows = Board::query()->where('user_id', $viewer->id)->orderBy('name')->get(['id', 'name']);

        foreach ($rows as $row) {
            $list[] = ['id' => $row->id, 'name' => $row->name];
        }

        $this->boards = $list;

        $savedIds = [];

        foreach (DB::table('board_piece')
            ->join('boards', 'boards.id', '=', 'board_piece.board_id')
            ->where('boards.user_id', $viewer->id)
            ->where('board_piece.piece_id', $this->piece->getKey())
            ->get(['boards.id']) as $row) {
            if (is_int($row->id)) {
                $savedIds[] = $row->id;
            } elseif (is_string($row->id) && ctype_digit($row->id)) {
                $savedIds[] = (int) $row->id;
            }
        }

        $this->savedBoardIds = $savedIds;
    }

    public function toggleOpen(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->open = ! $this->open;
    }

    public function saveTo(int $boardId): void
    {
        $viewer = Auth::user();

        if (! $viewer instanceof User) {
            return;
        }

        $board = Board::query()->find($boardId);

        if ($board !== null && (int) $board->user_id === $viewer->id) {
            app(SaveToBoard::class)->handle($viewer, $board, $this->piece);
        }

        $this->refreshBoards();
    }

    public function createAndSave(): void
    {
        $viewer = Auth::user();

        if (! $viewer instanceof User || trim($this->newBoardName) === '') {
            return;
        }

        $board = app(CreateBoard::class)->handle($viewer, trim($this->newBoardName));
        app(SaveToBoard::class)->handle($viewer, $board, $this->piece);

        $this->newBoardName = '';
        $this->refreshBoards();
    }
}
