<?php

namespace App\Http\Concerns;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

trait ResolvesActor
{
    protected function actor(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }
}
