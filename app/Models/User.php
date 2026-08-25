<?php

namespace App\Models;

use App\Domain\Identity\Models\Follow;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'bio',
        'locale',
        'avatar_path',
        'cover_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function setUsernameAttribute(string $value): void
    {
        $this->attributes['username'] = mb_strtolower($value);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return BelongsToMany<User, $this, Follow, 'pivot'> */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
            ->using(Follow::class);
    }

    /** @return BelongsToMany<User, $this, Follow, 'pivot'> */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->using(Follow::class);
    }

    public function isFollowing(User $user): bool
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    public function profileUrl(): string
    {
        return url('/@'.$this->username);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->hasRole('admin'),
            'seller' => $this->hasAnyRole(['seller', 'admin']),
            default => false,
        };
    }
}
