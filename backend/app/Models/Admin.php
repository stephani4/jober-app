<?php

namespace App\Models;

use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Сотрудник админки. Живёт отдельно от пользователей PWA.
 */
#[Fillable([
    'name',
    'email',
    'password',
])]
#[Hidden(['password', 'remember_token'])]
class Admin extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory, HasRoles;

    /**
     * Guard Spatie и JWT — не смешивать с пользователями PWA.
     */
    protected string $guard_name = 'admin';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'guard' => 'admin',
            'roles' => $this->getRoleNames()->values()->all(),
        ];
    }
}
