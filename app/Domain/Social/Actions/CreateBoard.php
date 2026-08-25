<?php

namespace App\Domain\Social\Actions;

use App\Domain\Social\Models\Board;
use App\Models\User;

final class CreateBoard
{
    public function handle(User $actor, string $name, ?string $description = null, bool $isPrivate = true): Board
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 100) {
            throw new \InvalidArgumentException(__('social.board_name_invalid'));
        }

        return Board::query()->create([
            'user_id' => $actor->id,
            'name' => $name,
            'description' => $description,
            'is_private' => $isPrivate,
        ]);
    }
}
