<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Services\GravatarService;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'avatar_path',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getAvatarUrlAttribute(): string
    {
        // Priority: uploaded avatar -> social avatar -> Gravatar
        if ($this->avatar_path) {
            return asset("storage/{$this->avatar_path}");
        }
        
        if ($this->avatar) {
            return $this->avatar;
        }
        
        return GravatarService::getGravatarUrl($this->email);
    }

    public function getInitialsAttribute(): string
    {
        $nameParts = explode(' ', $this->name);
        return collect($nameParts)
            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');
    }

    public function hasCustomAvatar(): bool
    {
        return !empty($this->avatar_path) || !empty($this->avatar);
    }
}
