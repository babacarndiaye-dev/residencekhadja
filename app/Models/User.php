<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'pos_pin', 'role', 'hotel_id', 'job_title', 'is_active'])]
#[Hidden(['password', 'pos_pin', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = [
        'admin' => 'Administrateur',
        'direction' => 'Direction',
        'reception' => 'Réception',
        'restaurant' => 'Restauration',
        'housekeeping' => 'Housekeeping',
        'maintenance' => 'Maintenance',
        'stock' => 'Économat / Stocks',
        'finance' => 'Finance & Comptabilité',
        'rh' => 'Ressources humaines',
        'marketing' => 'CRM & Marketing',
        'commercial' => 'Commercial & Événements',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pos_pin' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** A défini un PIN caisse (autorisations POS). */
    public function hasPosPin(): bool
    {
        return filled($this->pos_pin);
    }

    /** L'utilisateur a-t-il l'un des rôles donnés (admin passe toujours) ? */
    public function hasRole(string ...$roles): bool
    {
        return $this->role === 'admin' || in_array($this->role, $roles, true);
    }
}
