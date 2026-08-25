<?php

namespace App\Livewire;

use App\Domain\Identity\Actions\FollowUser;
use App\Domain\Identity\Actions\UnfollowUser;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class FollowButton extends Component
{
    public User $profile;

    public bool $following = false;

    public int $followersCount = 0;

    public function mount(User $profile): void
    {
        $viewer = Auth::user();

        $this->following = $viewer instanceof User && $viewer->isFollowing($profile);
        $this->followersCount = $profile->followers()->count();
    }

    public function toggle(): void
    {
        $viewer = Auth::user();

        if (! $viewer instanceof User) {
            $this->redirectRoute('login');

            return;
        }

        if ($this->following) {
            app(UnfollowUser::class)->handle($viewer, $this->profile);
            $this->following = false;
            $this->followersCount = max(0, $this->followersCount - 1);

            return;
        }

        try {
            app(FollowUser::class)->handle($viewer, $this->profile);
        } catch (DomainException) {
            return;
        }

        $this->following = true;
        $this->followersCount++;
    }

    public function render(): View
    {
        return view('livewire.follow-button');
    }
}
