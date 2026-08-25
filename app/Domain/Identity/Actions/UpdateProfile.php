<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Data\ProfileData;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

final class UpdateProfile
{
    public function handle(User $actor, User $user, ProfileData $data): User
    {
        Gate::forUser($actor)->authorize('update', $user);

        $user->name = $data->name;
        $user->username = $data->username;
        $user->bio = $data->bio;

        if ($data->avatar !== null) {
            $this->deletePrevious($user->avatar_path);
            $user->avatar_path = $this->storeProcessedImage($data->avatar, 'avatars', 512);
        }

        if ($data->cover !== null) {
            $this->deletePrevious($user->cover_path);
            $user->cover_path = $this->storeProcessedImage($data->cover, 'covers', 1600);
        }

        $user->save();

        return $user->refresh();
    }

    private function storeProcessedImage(UploadedFile $file, string $folder, int $maxEdge): string
    {
        $image = (new ImageManager(new Driver))
            ->decode($file->getRealPath())
            ->scaleDown(width: $maxEdge, height: $maxEdge);

        $encoded = $image->encodeUsingFormat(Format::JPEG, quality: 85);

        $path = $folder.'/'.Str::uuid().'.jpg';
        Storage::disk('public')->put($path, $encoded->toString());

        return $path;
    }

    private function deletePrevious(?string $path): void
    {
        if ($path !== null && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
