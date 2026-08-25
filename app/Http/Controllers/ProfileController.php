<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function show(Request $request, User $user): View
    {
        $viewer = $request->user();

        $user->loadCount(['followers', 'following']);

        return view('pages.profile', [
            'profile' => $user,
            'isSelf' => $viewer !== null && $viewer->is($user),
            'isFollowing' => $viewer !== null && $viewer->isFollowing($user),
        ]);
    }
}
