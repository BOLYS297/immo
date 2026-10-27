<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\RecordsActivity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'company', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, RecordsActivity;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_USER = 'user';

    /** Client du portail : se connecte sur le front (/connexion), jamais à l'admin. */
    public const ROLE_CLIENT = 'client';

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

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function portalItems()
    {
        return $this->hasMany(PortalItem::class);
    }

    /** Rôle de l'équipe (config/admin.php), anciens noms ramenés à leur rôle actuel ; null pour un client ou un rôle inconnu. */
    public function staffRole(): ?string
    {
        $role = config('admin.aliases')[$this->role] ?? $this->role;

        return array_key_exists((string) $role, config('admin.roles')) ? $role : null;
    }

    public function roleLabel(): string
    {
        return config('admin.roles')[$this->staffRole()]['label'] ?? ucfirst((string) $this->role);
    }

    /** Membre actif de l'équipe : peut se connecter à l'administration (ce que chacun y voit dépend de son rôle). */
    public function isStaff(): bool
    {
        return $this->is_active && $this->staffRole() !== null;
    }

    /** Administrateur : toutes les permissions. */
    public function isAdmin(): bool
    {
        return $this->is_active && $this->staffRole() === self::ROLE_ADMIN;
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->isStaff()) {
            return false;
        }
        $granted = config('admin.roles')[$this->staffRole()]['permissions'];

        return in_array('*', $granted, true) || in_array($permission, $granted, true);
    }

    public function scopeStaff($query)
    {
        return $query->whereIn('role', [...array_keys(config('admin.roles')), ...array_keys(config('admin.aliases'))]);
    }

    public function isClient(): bool
    {
        return $this->role === self::ROLE_CLIENT && $this->is_active;
    }

    public function scopeClients($query)
    {
        return $query->where('role', self::ROLE_CLIENT);
    }
}
