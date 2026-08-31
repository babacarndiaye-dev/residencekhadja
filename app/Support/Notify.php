<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Notification;

/**
 * Diffuse une alerte in-app (App\Notifications\StaffAlert) au personnel concerné.
 *
 *   Notify::roles(['reception', 'direction'], 'Nouvelle réservation', 'HRK-XXXX — Aminata Diop',
 *       route('admin.reservations.show', $reservation, false), level: 'success', icon: '📅');
 *
 * Le rôle « admin » reçoit toujours. Les comptes inactifs sont exclus.
 */
class Notify
{
    public static function roles(
        array|string $roles,
        string $title,
        string $body = '',
        ?string $url = null,
        string $level = 'info',
        string $icon = '🔔',
    ): void {
        $roles = array_values(array_unique(array_merge((array) $roles, ['admin'])));

        $users = User::query()
            ->where('is_active', true)
            ->whereIn('role', $roles)
            ->get();

        self::users($users, $title, $body, $url, $level, $icon);
    }

    public static function users(
        iterable $users,
        string $title,
        string $body = '',
        ?string $url = null,
        string $level = 'info',
        string $icon = '🔔',
    ): void {
        $users = collect($users)->filter();
        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new StaffAlert($title, $body, $url, $level, $icon));
    }
}
