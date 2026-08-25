<?php

namespace App\Http\Controllers\Social;

use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NotificationsController extends Controller
{
    use ResolvesActor;

    public function index(Request $request): View
    {
        $user = $this->actor($request);

        return view('pages.notifications', [
            'notifications' => $user->notifications()->latest()->paginate(20),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $user = $this->actor($request);

        $user->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
