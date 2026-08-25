<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Identity\Actions\UpdateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('pages.settings-profile');
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        app(UpdateProfile::class)->handle(
            actor: $request->actor(),
            user: $request->actor(),
            data: $request->profileData(),
        );

        return redirect()
            ->route('settings.profile.edit')
            ->with('status', __('shell.profile_updated'));
    }
}
