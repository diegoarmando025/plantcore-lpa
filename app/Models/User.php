<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use LogicException;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected static function booted(): void
    {
        static::deleting(function (self $user): void {
            if ($user->is_portfolio_owner) {
                throw new LogicException('La cuenta propietaria no puede eliminarse.');
            }
        });

        static::updating(function (self $user): void {
            if ($user->getOriginal('is_portfolio_owner') && ! $user->is_portfolio_owner) {
                throw new LogicException('No se puede retirar la protección de la cuenta propietaria.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_portfolio_owner' => 'boolean',
            'is_portfolio_demo_admin' => 'boolean',
        ];
    }
}
