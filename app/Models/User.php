<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLES = ['super_admin' => 'Super admin', 'admin' => 'Admin'];

    public const PERMISSIONS = ['access-dashboard', 'export-data', 'download-cards', 'update-members', 'import-members', 'manage-appointments', 'manage-positions', 'manage-users'];

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active || ! in_array($permission, self::PERMISSIONS, true)) {
            return false;
        }

        return $this->role === 'super_admin' || ($this->role === 'admin' && in_array($permission, ['access-dashboard', 'manage-appointments', 'manage-positions'], true));
    }

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'is_active' => 'boolean',
        ];
    }
}
